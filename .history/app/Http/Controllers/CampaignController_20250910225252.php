<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Models\User;
use App\ViewModels\Reports\CampaignReport;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Menampilkan daftar kampanye dengan filter, pencarian, dan pagination.
     */
    public function index(Request $request)
    {
        $query = Campaign::query()->with('owner');

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        // Searching
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where('name', 'like', $searchTerm);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $campaigns = $query->latest()->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get(); // Untuk dropdown filter

        return view('campaigns.index', compact('campaigns', 'users'));
    }

    /**
     * Menyimpan kampanye baru.
     */
    public function store(CampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());
        activity()->performedOn($campaign)->causedBy(auth()->user())->log("Membuat kampanye: {$campaign->name}");
        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil dibuat.');
    }

    /**
     * Menampilkan laporan detail kampanye menggunakan ViewModel.
     */
    public function show(Campaign $campaign)
    {
        $viewModel = new CampaignReport($campaign);
        return view('campaigns.show', ['report' => $viewModel]);
    }

    /**
     * Memperbarui kampanye yang ada.
     */
    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $campaign->update($request->validated());
        activity()->performedOn($campaign)->causedBy(auth()->user())->log("Memperbarui kampanye: {$campaign->name}");
        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil diperbarui.');
    }

    /**
     * Menghapus kampanye.
     */
    public function destroy(Campaign $campaign)
    {
        $campaignName = $campaign->name;
        $campaign->delete();
        activity()->causedBy(auth()->user())->log('Menghapus kampanye: ' . $campaignName);
        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil dihapus.');
    }
}
