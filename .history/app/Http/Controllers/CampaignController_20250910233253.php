<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Models\User;
use App\ViewModels\Reports\CampaignReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->integer('owner_id'));
        }

        // Searching
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->string('search') . '%';
            $query->where('name', 'like', $searchTerm);
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 10);
        $campaigns = $query->latest()->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get(); // Untuk dropdown filter

        return view('campaigns.index', compact('campaigns', 'users'));
    }

    /**
     * Menyimpan kampanye baru.
     */
    public function store(CampaignRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id(); // <-- WAJIB: set user pembuat kampanye

        // Jika model Campaign menggunakan $guarded dan menolak user_id,
        // ganti create() di bawah dengan Campaign::query()->create($data);
        DB::transaction(function () use (&$campaign, $data) {
            $campaign = Campaign::create($data);
        });

        activity()->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Membuat kampanye: {$campaign->name}");

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
     * (Tidak mengubah user_id agar jejak pembuat tetap konsisten.)
     */
    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $data = $request->validated();
        unset($data['user_id']); // pastikan tidak menimpa user_id

        $campaign->update($data);

        activity()->performedOn($campaign)
            ->causedBy(auth()->user())
            ->log("Memperbarui kampanye: {$campaign->name}");

        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil diperbarui.');
    }

    /**
     * Menghapus kampanye.
     */
    public function destroy(Campaign $campaign)
    {
        $campaignName = $campaign->name;
        $campaign->delete();

        activity()->causedBy(auth()->user())
            ->log('Menghapus kampanye: ' . $campaignName);

        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil dihapus.');
    }
}
