<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Http\Requests\CampaignRequest; // <-- Import CampaignRequest
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $campaigns = Campaign::latest()->paginate(10);
        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $campaign = new Campaign();
        return view('campaigns.create', compact('campaign'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());

        activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Created campaign: {$campaign->name}");

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign)
    {
        $activities = $campaign->activities()->latest()->get();
        return view('campaigns.show', compact('campaign', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campaign $campaign)
    {
        return view('campaigns.edit', compact('campaign'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $campaign->update($request->validated());

        activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Updated campaign: {$campaign->name}");

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
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

