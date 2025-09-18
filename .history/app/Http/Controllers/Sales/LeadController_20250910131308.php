<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with('user');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->latest()->paginate(10);
        return view('sales.leads.index', compact('leads'));
    }

    public function create()
    {
        return view('sales.leads.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'status' => 'required|in:trial,active,converted,churn',
        ]);

        $lead = Lead::create($validated + ['user_id' => Auth::id()]);

        $lead->activities()->create([
            'description' => 'Lead created',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    public function show(Lead $lead)
    {
        $lead->load(['activities.user' => function($query){
            $query->orderBy('created_at', 'desc');
        }]);
        return view('sales.leads.show', compact('lead'));
    }

    public function edit(Lead $lead)
    {
        return view('sales.leads.form', compact('lead'));
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'status' => 'required|in:trial,active,converted,churn',
        ]);

        $oldStatus = $lead->status;
        $lead->update($validated);

        $lead->activities()->create([
            'description' => "Lead updated. Status changed from {$oldStatus} to {$lead->status}.",
            'user_id' => Auth::id(),
            'metadata' => ['old_status' => $oldStatus, 'new_status' => $lead->status]
        ]);

        // Auto Subscription Logic
        if ($oldStatus !== 'converted' && $lead->status === 'converted') {
            // Logika untuk membuat subscription otomatis
            // Contoh sederhana:
            $lead->subscriptions()->create([
                'plan' => 'Basic Monthly',
                'status' => 'active',
                'amount' => 100000,
                'cycle' => 'monthly',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);

            $lead->activities()->create([
                'description' => 'Subscription automatically created upon conversion.',
                'user_id' => Auth::id(),
            ]);
        }


        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        // Aktivitas penghapusan bisa dicatat di sini jika perlu
        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }
}
