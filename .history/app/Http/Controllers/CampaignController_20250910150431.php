<?php

namespace App\Http\Controllers;

use App\Events\CampaignCreated; // Import Event
use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    // ... index, create, show, edit, update methods ...

    public function store(CampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());

        // --- NEW LOGIC ---
        // Fire the event after a campaign is successfully created
        CampaignCreated::dispatch($campaign);
        // --- END NEW LOGIC ---

        activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Created campaign: {$campaign->name}");

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    }

    // ... other methods ...
    public function index()
    {
        $campaigns = Campaign::latest()->paginate(10);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function show(Campaign $campaign)
    {
        return view('campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        return view('campaigns.edit', compact('campaign'));
    }

    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $campaign->update($request->validated());

        activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Updated campaign: {$campaign->name}");

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaignName = $campaign->name;
        $campaign->delete();

        activity()
            ->causedBy(auth()->user())
            ->log('Deleted campaign: ' . $campaignName);

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}

