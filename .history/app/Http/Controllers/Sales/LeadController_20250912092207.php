public function import(Request $request)
{
    $request->validate([
        'file' => ['required','file','mimes:xlsx,csv,txt'],
    ]);

    $rows = Excel::toArray([], $request->file('file'))[0] ?? [];
    if (empty($rows)) {
        return back()->withErrors(['file' => 'File kosong atau tidak dapat dibaca.']);
    }

    // --- Header mapping (Indonesia) ---
    $header = array_map(fn($h)=>\Illuminate\Support\Str::of((string)$h)->trim()->lower()->toString(), $rows[0] ?? []);
    $map = [
        'status'        => array_search('status', $header, true),
        'owner'         => array_search('nama owner', $header, true),
        'store_name'    => array_search('nama toko', $header, true),
        'created_at'    => array_search('tanggal daftar', $header, true),
        'trial_ends_at' => array_search('tanggal habis', $header, true),
        'phone'         => array_search('no. whatsapp', $header, true),
        'email'         => array_search('email', $header, true),
    ];
    foreach (['status','owner','store_name','created_at','trial_ends_at','phone','email'] as $k) {
        if ($map[$k] === false) {
            return back()->withErrors(['file'=>"Kolom '{$k}' tidak ditemukan di header."]);
        }
    }

    // --- Helpers ---
    $statusMap = [
        'aktif' => 'active', 'active' => 'active',
        'tidak aktif' => 'nonactive', 'nonaktif' => 'nonactive', 'non active' => 'nonactive',
        'konversi' => 'converted', 'converted' => 'converted',
        'dibatalkan' => 'churn', 'batal' => 'churn', 'cancel' => 'churn',
        'trial' => 'trial',
    ];
    $parseStatus = fn($v)=>$statusMap[\Illuminate\Support\Str::of((string)$v)->lower()->toString()] ?? 'trial';

    $normalizePhone = fn($v)=>$v ? preg_replace('/\D+/', '', (string)$v) : null;

    $parseDate = function ($v, bool $withTime=false) {
        if (!$v) return null;
        $formats = $withTime
            ? ['d M Y H:i:s','d-m-Y H:i:s','Y-m-d H:i:s','d/m/Y H:i:s']
            : ['d M Y','d-m-Y','Y-m-d','d/m/Y'];
        foreach ($formats as $f) { try { return \Carbon\Carbon::createFromFormat($f, trim((string)$v)); } catch (\Throwable $e) {} }
        try { return \Carbon\Carbon::parse($v); } catch (\Throwable $e) { return null; }
    };

    // Map user owner name -> id
    $usersByName = \App\Models\User::pluck('id','name')->mapWithKeys(
        fn($id,$n)=>[\Illuminate\Support\Str::lower(trim($n))=>$id]
    )->all();

    $created=0; $updated=0;

    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
        foreach (array_slice($rows,1) as $row) {
            if (!is_array($row) || count($row)===0) continue;

            $email = trim((string)($row[$map['email']] ?? ''));
            if ($email === '') continue;

            $ownerName  = trim((string)($row[$map['owner']] ?? ''));
            $ownerId    = $usersByName[\Illuminate\Support\Str::lower($ownerName)] ?? null;
            $storeName  = trim((string)($row[$map['store_name']] ?? ''));
            $createdAt  = $parseDate($row[$map['created_at']] ?? null, true);
            $trialEnds  = $parseDate($row[$map['trial_ends_at']] ?? null, false);

            // Fallback nama wajib: store_name -> ownerName -> email local part
            $leadName = $storeName !== '' ? $storeName
                        : ($ownerName !== '' ? $ownerName : \Illuminate\Support\Str::before($email, '@'));

            // Atur trial_ends_at minimal = created_at + 7 hari
            if (!$trialEnds && $createdAt) $trialEnds = $createdAt->copy()->addDays(7);
            if ($trialEnds && $createdAt && $trialEnds->lt($createdAt)) {
                $trialEnds = $createdAt->copy()->addDays(7);
            }

            $payload = [
                'status'        => $parseStatus($row[$map['status']] ?? 'trial'),
                'owner_id'      => $ownerId,
                'store_name'    => $storeName,
                'trial_ends_at' => $trialEnds,
                'phone'         => $normalizePhone($row[$map['phone']] ?? null),
            ];

            $lead = \App\Models\Lead::where('email',$email)->first();

            if ($lead) {
                // update: jangan timpa 'name' yang sudah ada
                $lead->fill($payload);
                if ($createdAt) $lead->created_at = $createdAt;
                $lead->saveQuietly();
                $updated++;
            } else {
                // create: wajib set 'name'
                $lead = new \App\Models\Lead($payload + [
                    'email' => $email,
                    'name'  => $leadName,
                ]);
                if ($createdAt) $lead->created_at = $createdAt;
                $lead->saveQuietly();
                $created++;
            }
        }

        \Illuminate\Support\Facades\DB::commit();
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        report($e);
        return back()->withErrors(['file' => 'Import gagal: '.$e->getMessage()]);
    }

    return redirect()->route('leads.index')->with('success', "Import selesai. Dibuat: {$created}, Diperbarui: {$updated}.");
}
