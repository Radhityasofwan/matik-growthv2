<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\WATemplate;
use App\Models\WahaSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    /** List Leads + filter + pagination (+ chat stats) */
    public function index(Request $request)
    {
        $query = Lead::query()->with(['owner', 'subscription']);

        // ---- Filters dasar ----
        if ($request->filled('search')) {
            $s = '%'.$request->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('email', 'like', $s)
                  ->orWhere('store_name', 'like', $s);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ---- Subquery chat stats (alias unik agar aman) ----
        $chatCountSub = DB::table('activity_log')
            ->selectRaw('COUNT(*)')
            ->whereColumn('activity_log.subject_id', 'leads.id')
            ->where('activity_log.subject_type', Lead::class)
            ->where('log_name', 'wa_chat');

        $lastChatSub = DB::table('activity_log')
            ->selectRaw('MAX(created_at)')
            ->whereColumn('activity_log.subject_id', 'leads.id')
            ->where('activity_log.subject_type', Lead::class)
            ->where('log_name', 'wa_chat');

        $lastReplySub = DB::table('activity_log')
            ->selectRaw('MAX(created_at)')
            ->whereColumn('activity_log.subject_id', 'leads.id')
            ->where('activity_log.subject_type', Lead::class)
            ->whereIn('log_name', ['wa_reply', 'wa_incoming']);

        $query->select('leads.*')
            ->selectSub($chatCountSub, 'chat_count_calc')
            ->selectSub($lastChatSub, 'last_wa_chat_at_calc')
            ->selectSub($lastReplySub, 'last_reply_at_calc');

        $perPage = (int) $request->input('per_page', 10);
        $leads   = $query->latest()->paginate($perPage)->withQueryString();

        // Map hasil subquery ke properti yang dipakai UI
        $leads->getCollection()->transform(function (Lead $lead) {
            $lead->chat_count       = (int) ($lead->chat_count_calc ?? 0);
            $lead->last_wa_chat_at  = $lead->last_wa_chat_at_calc ? Carbon::parse($lead->last_wa_chat_at_calc) : null;
            $lead->last_reply_at    = $lead->last_reply_at_calc ? Carbon::parse($lead->last_reply_at_calc) : null;
            return $lead;
        });

        // Dropdowns
        $users = User::orderBy('name')->get();
        $whatsappTemplates = Schema::hasTable('w_a_templates')
            ? WATemplate::orderBy('name')->get()
            : collect();

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

    /** Detail + Timeline Lead (klik nama dari index) */
    public function show(Lead $lead)
    {
        $lead->load(['owner', 'subscription']);

        $activities = $lead->activities()
            ->with('causer')
            ->latest()
            ->paginate(20);

        $statusLabel = [
            'trial'     => 'Trial',
            'active'    => 'Aktif',
            'nonactive' => 'Tidak Aktif',
            'converted' => 'Konversi',
            'churn'     => 'Dibatalkan',
        ];

        return view('sales.leads.show', compact('lead', 'activities', 'statusLabel'));
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

        // Validasi logis tambahan
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

    /** Hapus lead + relasi */
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
        foreach (['status','owner','store_name','created_at','trial_ends_at','phone','email'] as $k) {
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

        $created = 0;
        $updated = 0;

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

    // ======== WA helpers (opsional jika route diarahkan ke controller ini) ========

    /** Tandai lead sudah di-chat secara manual (untuk kategori bergerak otomatis) */
    public function markChatted(Lead $lead)
    {
        activity('wa_chat')->performedOn($lead)->causedBy(auth()->user())
            ->withProperties(['method' => 'manual_wa_link'])
            ->log('Manual WhatsApp (wa.me)');

        return response()->json(['success' => true]);
    }

    /** Kirim WA single via WAHA */
    public function sendWaToLead(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'sender_id' => ['required', 'integer', 'exists:waha_senders,id'],
            'message'   => ['required', 'string', 'max:4000'],
        ]);

        $phone = preg_replace('/\D+/', '', (string) ($lead->phone ?? ''));
        if (!$phone) {
            return response()->json(['success' => false, 'error' => 'Nomor WA tidak ditemukan.'], 422);
        }

        try {
            // Panggil service WAHA (asumsi ada)
            $svc = app(\App\Services\WahaService::class);
            $sender = WahaSender::findOrFail($data['sender_id']);
            $svc->sendText($sender, $phone, $data['message']);

            activity('wa_chat')->performedOn($lead)->causedBy(auth()->user())
                ->withProperties(['method' => 'waha', 'sender_id' => $sender->id])
                ->log('Mengirim pesan WhatsApp');

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'Gagal mengirim pesan.'], 500);
        }
    }

    /** Kirim WA bulk via WAHA (berdasar ID lead) */
    public function sendWaBulk(Request $request)
    {
        $data = $request->validate([
            'sender_id' => ['required', 'integer', 'exists:waha_senders,id'],
            'message'   => ['required', 'string', 'max:4000'],
            'lead_ids'  => ['required', 'array'],
            'lead_ids.*'=> ['integer', 'exists:leads,id'],
        ]);

        $sender = WahaSender::findOrFail($data['sender_id']);
        $svc    = app(\App\Services\WahaService::class);

        $leads = Lead::whereIn('id', $data['lead_ids'])->get();
        foreach ($leads as $lead) {
            $phone = preg_replace('/\D+/', '', (string) ($lead->phone ?? ''));
            if (!$phone) continue;

            $msg = str_replace(
                ['{{name}}','{{nama}}','{{nama_pelanggan}}','{{store_name}}','{{trial_ends_at}}'],
                [
                    $lead->name ?? '',
                    $lead->name ?? '',
                    $lead->name ?? '',
                    $lead->store_name ?? '',
                    optional($lead->trial_ends_at)->format('d M Y') ?? '',
                ],
                $data['message']
            );

            try {
                $svc->sendText($sender, $phone, $msg);
                activity('wa_chat')->performedOn($lead)->causedBy(auth()->user())
                    ->withProperties(['method' => 'waha', 'sender_id' => $sender->id, 'bulk' => true])
                    ->log('Mengirim pesan WhatsApp (bulk)');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['success' => true]);
    }
}
