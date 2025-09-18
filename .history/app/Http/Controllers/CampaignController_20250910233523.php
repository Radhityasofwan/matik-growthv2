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
        $query = Campaign::query()->with(['owner', 'creator']);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->integer('owner_id'));
        }

        // Searching
        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where('name', 'like', $search);
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 10);
        $campaigns = $query->latest()->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get();

        return view('campaigns.index', compact('campaigns', 'users'));
    }

    /**
     * Menyimpan kampanye baru.
     * user_id akan otomatis diisi oleh model (booted()).
     */
    public function store(CampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());

        activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Membuat kampanye: {$campaign->name}");

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Kampanye berhasil dibuat.');
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
     * Memperbarui kampanye yang ada (tanpa mengubah user_id).
     */
    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $data = $request->validated();
        unset($data['user_id']); // cegah overwrite

        $campaign->update($data);

        activity()
            ->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Memperbarui kampanye: {$campaign->name}");

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Kampanye berhasil diperbarui.');
    }

    /**
     * Menghapus kampanye.
     */
    public function destroy(Campaign $campaign)
    {
        $name = $campaign->name;
        $campaign->delete();

        activity()
            ->causedBy(auth()->user())
            ->log("Menghapus kampanye: {$name}");

        return redirect()
            ->route('campaigns.index')
            ->with('success', 'Kampanye berhasil dihapus.');
    }
}
