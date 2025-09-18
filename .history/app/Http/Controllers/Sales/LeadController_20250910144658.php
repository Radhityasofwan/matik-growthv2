<?php

namespace App\Http\Controllers\Sales;

use App\Events\TrialCreated; // <-- Import Event
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    // ... index, create methods are unchanged ...
    public function index()
    {
        $query = Lead::query();

        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%')
                  ->orWhere('email', 'like', '%' . request('search') . '%');
        }

        if (request('status') && request('status') != 'all') {
            $query->where('status', request('status'));
        }

        $leads = $query->latest()->paginate(10);
        return view('sales.leads.index', compact('leads'));
    }

    public function create()
    {
        $lead = new Lead();
        return view('sales.leads.form', compact('lead'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(LeadRequest $request)
    {
        $lead = Lead::create($request->validated());

        // --- NEW LOGIC ---
        // If the new lead is a trial, fire the TrialCreated event.
        if ($lead->status === 'trial') {
            TrialCreated::dispatch($lead);
        }
        // --- END NEW LOGIC ---

        activity()
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log("Created lead: {$lead->name}");

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    // ... show, edit, update, destroy methods are unchanged ...
    public function show(Lead $lead)
    {
        $activities = $lead->activities()->latest()->paginate(10);
        return view('sales.leads.show', compact('lead', 'activities'));
    }

    public function edit(Lead $lead)
    {
        return view('sales.leads.form', compact('lead'));
    }

    public function update(LeadRequest $request, Lead $lead)
    {
        $originalStatus = $lead->status;
        $lead->update($request->validated());

        if ($originalStatus !== 'converted' && $lead->status === 'converted') {
            // Logic to create subscription can be added here
        }

        activity()
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log("Updated lead: {$lead->name}");

        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead)
    {
        $leadName = $lead->name;
        $lead->delete();

        activity()
            ->causedBy(auth()->user())
            ->log('Deleted lead: ' . $leadName);

        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully.');
    }
}

