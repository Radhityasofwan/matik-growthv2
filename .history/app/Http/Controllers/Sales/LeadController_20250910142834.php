<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Http\Requests\LeadRequest; // <-- Import LeadRequest
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lead::with('owner')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->paginate(10);

        return view('sales.leads.index', compact('leads'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lead = new Lead();
        $users = User::all();
        return view('sales.leads.form', compact('lead', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    // Validation is now handled by LeadRequest
    public function store(LeadRequest $request)
    {
        $lead = Lead::create($request->validated());

        // Log activity
        activity()
           ->performedOn($lead)
           ->causedBy(auth()->user())
           ->log('Created lead: ' . $lead->name);

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead)
    {
        $activities = $lead->activities()->latest()->get();
        return view('sales.leads.show', compact('lead', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lead $lead)
    {
        $users = User::all();
        return view('sales.leads.form', compact('lead', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    // Validation is now handled by LeadRequest
    public function update(LeadRequest $request, Lead $lead)
    {
        $lead->update($request->validated());

        // Log activity
        activity()
           ->performedOn($lead)
           ->causedBy(auth()->user())
           ->log('Updated lead details for: ' . $lead->name);

        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        $leadName = $lead->name;
        $lead->delete();

        // Log activity
        activity()
           ->causedBy(auth()->user())
           ->log('Deleted lead: ' . $leadName);

        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }
}

