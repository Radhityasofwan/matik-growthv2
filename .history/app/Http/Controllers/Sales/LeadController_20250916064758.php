<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\WATemplate;
use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    /** List Leads + filter + pagination */
    public function index(Request $request)
    {
        $query = Lead::query()->with(['owner', 'subscription']);

        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(fn ($q) => $q->where('name','like',$s)->orWhere('email','like',$s)->orWhere('store_name','like',$s));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $leads   = $query->latest()->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get();
        $whatsappTemplates = WATemplate::all();

        $wahaSenders = WahaSender::query()
            ->where('is_active', true)
            ->when(
                Schema::hasColumn('waha_senders', 'name'),
                fn ($q) => $q->orderBy('name'),
                fn ($q) => $q->orderBy('id')
            )
            ->get();

        return view('sales.leads.index', compact('leads', 'users', 'whatsappTemplates', 'wahaSenders'));
    }

    /** Store single lead */
    public function store(LeadRequest $request)
    {
        $data = $request->validated();
        $registeredAt = $request->input('registered_at');
        unset($data['registered_at']);

        DB::transaction(function () use ($data, $registeredAt) {
            $lead = Lead::create($data);

            if ($registeredAt) {
                $lead->created_at = Carbon::parse($registeredAt);
                if ($lead->trial_ends_at && $lead->trial_ends_at->lt($lead->created_at)) {
                    $lead->trial_ends_at = $lead->created_at->copy()->addDays(7);
                }
                if (empty($data['trial_ends_at'])) {
                    $lead->trial_ends_at = $lead->created_at->copy()->addDays(7);
                }
                $lead->save();
            }

            activity()->performedOn($lead)->causedBy(auth()->user())->log("Membuat lead: {$lead->name}");
        });

        return redirect()->route('leads.index')->with('success', 'Lead berhasil dibuat.');
    }

    /** Update single lead (+ subscription jika converted) */
    public function update(LeadRequest $request, Lead $lead)
    {
        $data = $request->validated();
        $registeredAt = $request->input('registered_at');
        unset($data['registered_at']);

        if ($registeredAt && !empty($data['trial_ends_at'])) {
            $ra = Carbon::parse($registeredAt);
            $te = Carbon::parse($data['trial_ends_at']);
            if ($te->lt($ra)) {
                return back()
                    ->withErrors(['trial_ends_at' => 'Tanggal Habis tidak boleh sebelum Tanggal Daftar.'])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $lead, $data, $registeredAt) {
            $lead->update($data);

            if ($registeredAt) {
                $lead->created_at = Carbon::parse($registeredAt);
                if ($lead->trial_ends_at && $lead->trial_ends_at->lt($lead->created_at)) {
                    $lead->trial_ends_at = $lead->created_at->copy()->addDays(7);
                }
                $lead->save();
            }

            if ($request->input('status') === 'converted') {
                $subscriptionData = $request->validate([
                    'plan'       => 'required|string|max:255',
                    'amount'     => 'required|numeric|min:0',
                    'cycle'      => 'required|in:monthly,yearly',
                    'start_date' => 'required|date',
                    'end_date'   => 'nullable|date|after_or_equal:start_date',
                ]);

                $lead->subscription()->updateOrCreate(
                    ['lead_id' => $lead->id],
                    $subscriptionData + ['status' => 'active']
                );
            }

            activity()->performedOn($lead)->causedBy(auth()->user())->log("Memperbarui lead: {$lead->name}");
        });

        return redirect()->route('leads.index')->with('success', 'Lead berhasil diperbarui.');
    }

    /** Hapus Lead (aman terhadap relasi) */
    public function destroy(Lead $lead)
    {
        DB::transaction(function () use ($lead) {
            if (method_exists($lead, 'subscription')) {
                $lead->subscription()->delete();
            }
            if (method_exists($lead, 'subscriptions')) {
                $lead->subscriptions()->delete();
            }

            $lead->delete();
            activity()->causedBy(auth()->user())->log("Menghapus lead: {$lead->name}");
        });

        if (Lead::find($lead->id)) {
            return back()->withErrors(['delete' => 'Lead gagal dihapus, coba lagi.']);
        }

        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus.');
    }

    /**
     * Import dari template (Status, Nama Owner, Nama Toko, Tanggal Daftar,
     * Tanggal Habis, No. Whatsapp, Email)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ]);

        $rows = Excel::toArray([], $request->file('file'))[0] ?? [];
        if (empty($rows)) {
            return back()->withErrors(['file' => 'File kosong atau tidak dapat dibaca.']);
        }

        $header = array_map(fn ($h) => Str::of((string) $h)->trim()->lower()->toString(), $rows[0] ?? []);
        $map = [
            'status'        => array_search('status', $header, true),
            'owner'         => array_search('nama owner', $header, true),
            'store_name'    => array_search('nama toko', $header, true),
            'created_at'    => array_search('tanggal daftar', $header, true),
            'trial_ends_at' => array_search('tanggal habis', $header, true),
            'phone'         => array_search('no. whatsapp', $header, true),
            'email'         => array_search('email', $header, true),
        ];
        foreach (['status', 'owner', 'store_name', 'created_at', 'trial_ends_at', 'phone', 'email'] as $k) {
            if ($map[$k] === false) {
                return back()->withErrors(['file' => "Kolom '{$k}' tidak ditemukan di header."]);
            }
        }

        $statusMap = [
            'aktif' => 'active', 'active' => 'active',
            'tidak aktif' => 'nonactive', 'nonaktif' => 'nonactive', 'non active' => 'nonactive',
            'konversi' => 'converted', 'converted' => 'converted',
            'dibatalkan' => 'churn', 'batal' => 'churn', 'cancel' => 'churn',
            'trial' => 'trial',
        ];
        $parseStatus = fn ($v) => $statusMap[Str::of((string) $v)->lower()->toString()] ?? 'trial';
        $normalizePhone = fn ($v) => $v ? preg_replace('/\D+/', '', (string) $v) : null;

        $parseDate = function ($v, bool $withTime = false) {
            if (!$v) return null;
            $formats = $withTime
                ? ['d M Y H:i:s', 'd-m-Y H:i:s', 'Y-m-d H:i:s', 'd/m/Y H:i:s']
                : ['d M Y', 'd-m-Y', 'Y-m-d', 'd/m/Y'];
            foreach ($formats as $f) {
                try { return Carbon::createFromFormat($f, trim((string) $v)); } catch (\Throwable $e) {}
            }
            try { return Carbon::parse($v); } catch (\Throwable $e) { return null; }
        };

        $usersByName = User::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [Str::lower(trim($n)) => $id])->all();

        $created = 0; $updated = 0;

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $row) {
                if (!is_array($row) || count($row) === 0) continue;

                $email = trim((string) ($row[$map['email']] ?? ''));
                if ($email === '') continue;

                $ownerName = trim((string) ($row[$map['owner']] ?? ''));
                $ownerId   = $usersByName[Str::lower($ownerName)] ?? null;
                $storeName = trim((string) ($row[$map['store_name']] ?? ''));
                $createdAt = $parseDate($row[$map['created_at']] ?? null, true);
                $trialEnds = $parseDate($row[$map['trial_ends_at']] ?? null, false);

                $leadName = $storeName !== '' ? $storeName : ($ownerName !== '' ? $ownerName : Str::before($email, '@'));

                if (!$trialEnds && $createdAt) $trialEnds = $createdAt->copy()->addDays(7);
                if ($trialEnds && $createdAt && $trialEnds->lt($createdAt)) {
                    $trialEnds = $createdAt->copy()->addDays(7);
                }

                $payload = [
                    'name'          => $leadName,
                    'status'        => $parseStatus($row[$map['status']] ?? 'trial'),
                    'owner_id'      => $ownerId,
                    'store_name'    => $storeName,
                    'trial_ends_at' => $trialEnds,
                    'phone'         => $normalizePhone($row[$map['phone']] ?? null),
                ];

                $lead = Lead::where('email', $email)->first();

                if ($lead) {
                    if (empty($lead->name)) $lead->name = $leadName;
                    $lead->fill(collect($payload)->except('name')->toArray());
                    if ($createdAt) $lead->created_at = $createdAt;
                    $lead->saveQuietly();
                    $updated++;
                } else {
                    $lead = new Lead($payload + ['email' => $email]);
                    if ($createdAt) $lead->created_at = $createdAt;
                    $lead->saveQuietly();
                    $created++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['file' => 'Import gagal: ' . $e->getMessage()]);
        }

        return redirect()->route('leads.index')->with('success', "Import selesai. Dibuat: {$created}, Diperbarui: {$updated}.");
    }

    /* ================= WHATSAPP: SINGLE, BULK, & MANUAL MARKER ================= */

    public function waSend(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'sender_id' => ['nullable', 'integer', 'exists:waha_senders,id'],
            'message'   => ['required', 'string', 'max:2000'],
        ]);

        if (!$lead->phone) {
            return response()->json(['success' => false, 'error' => 'Lead tidak punya nomor WhatsApp.'], 422);
        }

        $sender = $this->pickSender($data['sender_id'] ?? null);
        if (!$sender) {
            return response()->json(['success' => false, 'error' => 'Tidak ada WA sender aktif.'], 422);
        }

        /** @var WahaService $svc */
        $svc = app(WahaService::class);
        $resp = $svc->sendMessage($sender, $lead->phone, $data['message']);

        $ok = (bool)($resp['success'] ?? false);
        if ($ok) $lead->bumpChatCount(1);

        activity()->performedOn($lead)->causedBy(auth()->user())
            ->withProperties([
                'sender_id' => $sender->id,
                'number'    => $sender->number ?? null,
                'message'   => Str::limit($data['message'], 120),
                'result'    => $resp,
            ])->log($ok ? 'WA terkirim' : 'WA gagal');

        return response()->json([
            'success'     => $ok,
            'lead_id'     => $lead->id,
            'message_id'  => $resp['message_id'] ?? null,
            'path'        => $resp['path'] ?? null,
            'error'       => $ok ? null : ($resp['error'] ?? 'Gagal mengirim.'),
            'chat_count'  => $lead->chat_count,
        ], $ok ? 200 : 502);
    }

    public function waBulkSend(Request $request)
    {
        $data = $request->validate([
            'sender_id' => ['nullable', 'integer', 'exists:waha_senders,id'],
            'message'   => ['required', 'string', 'max:2000'],
            'lead_ids'  => ['required', 'array', 'min:1'],
            'lead_ids.*'=> ['integer', 'exists:leads,id'],
        ]);

        $sender = $this->pickSender($data['sender_id'] ?? null);
        if (!$sender) {
            return response()->json(['success' => false, 'error' => 'Tidak ada WA sender aktif.'], 422);
        }

        $svc = app(WahaService::class);
        $leads = Lead::whereIn('id', $data['lead_ids'])->get();

        $results = []; $sent = 0; $attempted = 0;

        foreach ($leads as $lead) {
            if (!$lead->phone) {
                $results[] = ['lead_id' => $lead->id, 'success' => false, 'error' => 'No WA kosong'];
                continue;
            }
            $attempted++;
            try {
                $resp = $svc->sendMessage($sender, $lead->phone, $data['message']);
                $ok   = (bool)($resp['success'] ?? false);
                if ($ok) {
                    $lead->bumpChatCount(1);
                    $sent++;
                }

                $results[] = [
                    'lead_id'    => $lead->id,
                    'success'    => $ok,
                    'message_id' => $resp['message_id'] ?? null,
                    'error'      => $ok ? null : ($resp['error'] ?? 'Gagal'),
                    'chat_count' => $lead->chat_count,
                ];

                activity()->performedOn($lead)->causedBy(auth()->user())
                    ->withProperties([
                        'bulk'      => true,
                        'sender_id' => $sender->id,
                        'message'   => Str::limit($data['message'], 120),
                        'result'    => $resp,
                    ])->log($ok ? 'WA terkirim (bulk)' : 'WA gagal (bulk)');
            } catch (\Throwable $e) {
                $results[] = ['lead_id' => $lead->id, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success'    => true,
            'requested'  => count($data['lead_ids']),
            'attempted'  => $attempted,
            'sent_count' => $sent,
            'results'    => $results,
        ]);
    }

    /** Penanda saat pengguna memilih kirim via link wa.me (manual chat) */
    public function markChatted(Request $request, Lead $lead)
    {
        $lead->bumpChatCount(1);

        activity()->performedOn($lead)->causedBy(auth()->user())
            ->withProperties(['manual' => true])
            ->log('Lead ditandai sudah di-chat (manual)');

        return response()->json(['success' => true, 'chat_count' => $lead->chat_count]);
    }

    /* =========================== Helpers =========================== */

    protected function pickSender(?int $senderId): ?WahaSender
    {
        if ($senderId) {
            $s = WahaSender::where('id', $senderId)->where('is_active', true)->first();
            if ($s) return $s;
        }

        $byDefault = WahaSender::where('is_active', true)->where('is_default', true)->first();
        if ($byDefault) return $byDefault;

        return WahaSender::where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first();
    }
}
