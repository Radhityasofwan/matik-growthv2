<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with('owner')->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->paginate(10);

        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
            'channel' => 'required|in:WA,Ads,Content',
            'status' => 'required|in:planning,active,completed,on_hold',
        ]);

        $campaign = auth()->user()->campaigns()->create($validated);

        // Log activity
        activity()->performedOn($campaign)->log('Created campaign: ' . $campaign->name);

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['tasks', 'owner', 'activities' => function ($query) {
            $query->latest();
        }]);

        return view('campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign)
    {
        return view('campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'required|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'channel' => 'required|in:WA,Ads,Content',
            'status' => 'required|in:planning,active,completed,on_hold',
        ]);

        $campaign->update($validated);

        // Log activity
        activity()->performedOn($campaign)->log('Updated campaign details');

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaignName = $campaign->name;
        $campaign->delete();

        // Log activity
        activity()->log('Deleted campaign: ' . $campaignName);

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}
