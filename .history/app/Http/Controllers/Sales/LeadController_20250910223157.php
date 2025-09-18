<?php

namespace App\Http\Controllers\Sales;

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

        // --- INI PERBAIKANNYA ---
        // Mengambil nilai per_page dari request, dengan nilai default 10
        $perPage = $request->input('per_page', 10);

        $leads = $query->latest()->paginate($perPage)->withQueryString();
        // --- AKHIR PERBAIKAN ---

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

        activity()->performedOn($lead)->causedBy(auth()->user())->log("Membuat lead: {$lead->name}");
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dibuat.');
    }

    /**
     * Memperbarui data lead dan membuat/memperbarui langganan jika status 'converted'.
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
        return redirect()->route('leads.index')->with('success', 'Lead dan langganan berhasil diperbarui.');
    }

    /**
     * Menghapus data lead dari database.
     */
    public function destroy(Lead $lead)
    {
        $leadName = $lead->name;
        $lead->delete();

        activity()->causedBy(auth()->user())->log('Menghapus lead: ' . $leadName);
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus.');
    }
}
