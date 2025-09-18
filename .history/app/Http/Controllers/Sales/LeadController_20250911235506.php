<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

public function import(Request $request)
{
    $request->validate([
        'file' => ['required','file','mimes:xlsx,csv,txt'],
    ]);

    // Baca file dengan Laravel Excel → sebagai array
    $rows = Excel::toArray([], $request->file('file'))[0] ?? [];

    if (empty($rows)) {
        return back()->withErrors(['file' => 'File kosong atau tidak dapat dibaca.']);
    }

    // Ekspektasi header:
    // Status | Nama Owner | Nama Toko | Tanggal Daftar | Tanggal Habis | No. Whatsapp | Email
    // Normalisasi header (baris pertama)
    $header = array_map(fn($h) => Str::of($h)->trim()->lower()->toString(), $rows[0]);
    $map = [
        'status'          => array_search('status', $header, true),
        'owner'           => array_search('nama owner', $header, true),
        'store_name'      => array_search('nama toko', $header, true),
        'created_at'      => array_search('tanggal daftar', $header, true),
        'trial_ends_at'   => array_search('tanggal habis', $header, true),
        'phone'           => array_search('no. whatsapp', $header, true),
        'email'           => array_search('email', $header, true),
    ];

    // Validasi minimal kolom penting
    foreach (['status','owner','store_name','created_at','trial_ends_at','phone','email'] as $key) {
        if ($map[$key] === false) {
            return back()->withErrors(['file' => "Kolom '{$key}' tidak ditemukan di header."]);
        }
    }

    // Helper: mapping status dari teks template → enum DB
    $statusMap = [
        'aktif'     => 'active',
        'active'    => 'active',
        'trial'     => 'trial',
        'converted' => 'converted',
        'churn'     => 'churn',
    ];
    $parseStatus = fn($v) => $statusMap[Str::of($v)->lower()->toString()] ?? 'trial';

    // Helper: normalisasi no WA → hanya digit (boleh awali 0/62)
    $normalizePhone = function (string $v = null) {
        if (!$v) return null;
        $digits = preg_replace('/\D+/', '', $v);
        // Jika diawali 62/0 biarkan, lainnya kembalikan digit apa adanya
        return $digits;
    };

    // Helper: parse tanggal (menerima "11 Sep 2025 19:25:07" atau "18 Sep 2025")
    $parseDate = function ($v, $withTime = false) {
        if (!$v) return null;
        $formats = $withTime
            ? ['d M Y H:i:s', 'd-m-Y H:i:s', 'Y-m-d H:i:s', 'd/m/Y H:i:s']
            : ['d M Y', 'd-m-Y', 'Y-m-d', 'd/m/Y'];
        foreach ($formats as $f) {
            try { return Carbon::createFromFormat($f, trim($v)); } catch (\Throwable $e) {}
        }
        // fallback: biarkan Carbon coba parse bebas
        try { return Carbon::parse($v); } catch (\Throwable $e) { return null; }
    };

    // Ambil daftar user sekali untuk mapping cepat
    $usersByName = User::query()->pluck('id','name')->mapWithKeys(function($id,$name){
        return [Str::lower(trim($name)) => $id];
    })->all();

    $created = 0; $updated = 0;

    DB::beginTransaction();
    try {
        // Mulai dari baris kedua (data)
        foreach (array_slice($rows, 1) as $row) {
            if (!is_array($row) || count($row) === 0) continue;

            $email = trim((string)($row[$map['email']] ?? ''));
            if ($email === '') continue; // wajib ada email (unik)

            $ownerName = trim((string)($row[$map['owner']] ?? ''));
            $ownerId = $usersByName[Str::lower($ownerName)] ?? null;

            $payload = [
                'status'        => $parseStatus($row[$map['status']] ?? 'trial'),
                'owner_id'      => $ownerId,
                'store_name'    => trim((string)($row[$map['store_name']] ?? '')),
                'trial_ends_at' => $parseDate($row[$map['trial_ends_at']] ?? null, false),
                'phone'         => $normalizePhone($row[$map['phone']] ?? null),
                // name opsional—bila Anda ingin simpan nama owner/toko ke name, biarkan kosong atau isi dari toko
                // 'name'       => ??? (opsional)
            ];

            // Cari lead by email → upsert
            $lead = Lead::where('email', $email)->first();

            if ($lead) {
                // Update existing (hindari mass assignment untuk created_at)
                $lead->fill($payload);
                // created_at tidak diubah saat update
                $lead->saveQuietly();
                $updated++;
            } else {
                // Buat baru, set created_at dari sheet
                $lead = new Lead();
                $lead->fill($payload + ['email' => $email]);
                // Set created_at sesuai “Tanggal Daftar”
                $customCreated = $parseDate($row[$map['created_at']] ?? null, true);
                if ($customCreated) $lead->created_at = $customCreated;
                $lead->saveQuietly();
                $created++;
            }
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->withErrors(['file' => 'Import gagal: '.$e->getMessage()]);
    }

    return redirect()->route('leads.index')->with('success', "Import selesai. Dibuat: {$created}, Diperbarui: {$updated}.");
}
