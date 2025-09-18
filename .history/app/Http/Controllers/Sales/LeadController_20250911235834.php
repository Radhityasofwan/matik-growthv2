<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\WATemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Jika pakai Laravel Excel (disarankan)
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    /**
     * List Leads + filter + pagination.
     */
    public function index(Request $request)
    {
        $query = Lead::query()->with(['owner', 'subscription']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('store_name', 'like', $searchTerm);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $leads = $query->latest()->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get();
        $whatsappTemplates = WATemplate::all();

        return view('sales.leads.index', compact('leads', 'users', 'whatsappTemplates'));
    }

    /**
     * Store Lead baru (single input form).
     */
    public function store(LeadRequest $request)
    {
        $lead = Lead::create($request->validated());

        activity()->performedOn($lead)->causedBy(auth()->user())->log("Membuat lead: {$lead->name}");
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dibuat.');
    }

    /**
     * Update Lead (single input form) + optional subscription jika converted.
     */
    public function update(LeadRequest $request, Lead $lead)
    {
        $lead->update($request->validated());

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
        return redirect()->route('leads.index')->with('success', 'Lead berhasil diperbarui.');
    }

    /**
     * Hapus Lead.
     */
    public function destroy(Lead $lead)
    {
        $leadName = $lead->name;
        $lead->delete();

        activity()->causedBy(auth()->user())->log("Menghapus lead: {$leadName}");
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus.');
    }

    /**
     * Import Leads dari template (xlsx/csv).
     * Header yang diharapkan:
     * Status | Nama Owner | Nama Toko | Tanggal Daftar | Tanggal Habis | No. Whatsapp | Email
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ]);

        // Baca file → sheet pertama sebagai array baris
        $rows = Excel::toArray([], $request->file('file'))[0] ?? [];
        if (empty($rows)) {
            return back()->withErrors(['file' => 'File kosong atau tidak dapat dibaca.']);
        }

        // Normalisasi header
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

        foreach (['status', 'owner', 'store_name', 'created_at', 'trial_ends_at', 'phone', 'email'] as $key) {
            if ($map[$key] === false) {
                return back()->withErrors(['file' => "Kolom '{$key}' tidak ditemukan di header."]);
            }
        }

        // Helper mapping status idempotent
        $statusMap = [
            'aktif'     => 'active',
            'active'    => 'active',
            'trial'     => 'trial',
            'converted' => 'converted',
            'churn'     => 'churn',
        ];
        $parseStatus = fn ($v) => $statusMap[Str::of((string) $v)->lower()->toString()] ?? 'trial';

        // Normalisasi nomor telepon → digit saja
        $normalizePhone = function (?string $v) {
            if (!$v) return null;
            return preg_replace('/\D+/', '', $v);
        };

        // Parser tanggal fleksibel
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

        // Cache users by name (lower)
        $usersByName = User::query()->pluck('id', 'name')->mapWithKeys(function ($id, $name) {
            return [Str::lower(trim($name)) => $id];
        })->all();

        $created = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $row) {
                if (!is_array($row) || count($row) === 0) {
                    continue;
                }

                $email = trim((string) ($row[$map['email']] ?? ''));
                if ($email === '') {
                    continue; // email wajib
                }

                $ownerName = trim((string) ($row[$map['owner']] ?? ''));
                $ownerId   = $usersByName[Str::lower($ownerName)] ?? null;

                $payload = [
                    'status'        => $parseStatus($row[$map['status']] ?? 'trial'),
                    'owner_id'      => $ownerId,
                    'store_name'    => trim((string) ($row[$map['store_name']] ?? '')),
                    'trial_ends_at' => $parseDate($row[$map['trial_ends_at']] ?? null, false),
                    'phone'         => $normalizePhone((string) ($row[$map['phone']] ?? null)),
                ];

                $lead = Lead::where('email', $email)->first();

                if ($lead) {
                    $lead->fill($payload);
                    $lead->saveQuietly();
                    $updated++;
                } else {
                    $lead = new Lead();
                    $lead->fill($payload + ['email' => $email]);

                    // created_at dari sheet
                    $customCreated = $parseDate($row[$map['created_at']] ?? null, true);
                    if ($customCreated) {
                        $lead->created_at = $customCreated;
                    }

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
}
