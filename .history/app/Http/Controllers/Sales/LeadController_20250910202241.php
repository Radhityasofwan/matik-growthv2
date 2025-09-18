// app/Http/Controllers/Sales/LeadController.php
<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Models\WATemplate;
use App\Models\Subscription; // <-- Tambahkan ini
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query()->with(['owner', 'subscription']); // Tambah relasi subscription
        if ($request->filled('search')) { /* ... logika search ... */ }
        if ($request->filled('status')) { /* ... logika status ... */ }
        $leads = $query->latest()->paginate(10)->withQueryString();
        $users = User::orderBy('name')->get();
        $whatsappTemplates = WATemplate::all();
        return view('sales.leads.index', compact('leads', 'users', 'whatsappTemplates'));
    }

    public function store(LeadRequest $request)
    {
        Lead::create($request->validated());
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dibuat.');
    }

    public function update(Request $request, Lead $lead)
    {
        // Validasi data lead
        $validatedLead = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:trial,active,converted,churn',
            'owner_id' => 'required|exists:users,id',
        ]);

        $lead->update($validatedLead);

        // Jika status diubah menjadi "converted" dan ada data langganan
        if ($request->status == 'converted' && $request->filled('plan')) {
            $validatedSubscription = $request->validate([
                'plan' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'cycle' => 'required|in:monthly,yearly',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            // Buat atau perbarui langganan yang terhubung dengan lead ini
            Subscription::updateOrCreate(
                ['lead_id' => $lead->id],
                $validatedSubscription
            );
        }

        return redirect()->route('leads.index')->with('success', 'Lead berhasil diperbarui.');
    }

    // ... (method destroy tetap sama)
    public function destroy(Lead $lead)
    {
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus.');
    }
}
