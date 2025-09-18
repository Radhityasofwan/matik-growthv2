<?php

namespace App\Http\Controllers\Sales;

use App\Events\TrialCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\WATemplate;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Menampilkan daftar semua lead.
     */
    public function index(Request $request)
    {
        $query = Lead::query()->with(['owner', 'subscription']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->latest()->paginate(10)->withQueryString();
        $users = User::orderBy('name')->get();
        $whatsappTemplates = WATemplate::all();

        return view('sales.leads.index', compact('leads', 'users', 'whatsappTemplates'));
    }

    /**
     * Menyimpan data lead baru ke database.
     */
    public function store(LeadRequest $request)
    {
        $lead = Lead::create($request->validated());

        if ($lead->status === 'trial') {
            TrialCreated::dispatch($lead);
        }

        activity()->performedOn($lead)->causedBy(auth()->user())->log("Membuat lead: {$lead->name}");
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dibuat.');
    }

    /**
     * Memperbarui data lead dan membuat/memperbarui langganan jika status 'converted'.
     */
    public function update(LeadRequest $request, Lead $lead)
    {
        // 1. Update data lead terlebih dahulu
        $lead->update($request->validated());

        // 2. Jika status adalah 'converted', proses data langganan
        if ($request->input('status') === 'converted') {
            // Validasi data langganan yang dikirim dari form
            $subscriptionData = $request->validate([
                'plan'       => 'required|string|max:255',
                'amount'     => 'required|numeric|min:0',
                'cycle'      => 'required|in:monthly,yearly',
                'start_date' => 'required|date',
                'end_date'   => 'nullable|date|after_or_equal:start_date',
            ]);

            // 3. Gunakan updateOrCreate untuk membuat langganan baru atau update yang sudah ada
            // Ini mencegah duplikasi dan menangani pengeditan dengan baik.
            $lead->subscription()->updateOrCreate(
                ['lead_id' => $lead->id], // Kondisi pencarian
                $subscriptionData + ['status' => 'active'] // Data untuk diisi
            );
        }

        activity()->performedOn($lead)->causedBy(auth()->user())->log("Memperbarui lead: {$lead->name}");
        return redirect()->route('leads.index')->with('success', 'Lead dan langganan berhasil diperbarui.');
    }

    /**
     * Menghapus data lead dari database.
     */
    public function destroy(Lead $lead)
    {
        $leadName = $lead->name;
        $lead->delete(); // Langganan akan terhapus otomatis karena onDelete('cascade')

        activity()->causedBy(auth()->user())->log('Menghapus lead: ' . $leadName);
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus.');
    }
}

