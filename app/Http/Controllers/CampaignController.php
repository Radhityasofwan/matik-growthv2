<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Models\User;
use App\ViewModels\Reports\CampaignReport;
use Illuminate\Http\Request;
use App\Notifications\GenericDbNotification;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query()->with(['owner','creator']);

        if ($request->filled('status'))   $query->where('status', $request->string('status'));
        if ($request->filled('owner_id')) $query->where('owner_id', $request->integer('owner_id'));

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where('name', 'like', $search);
        }

        $perPage   = (int) $request->input('per_page', 10);
        $campaigns = $query->latest()->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get();

        return view('campaigns.index', compact('campaigns','users'));
    }

    public function store(CampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());

        activity()->performedOn($campaign)->causedBy($request->user())->log("Membuat kampanye: {$campaign->name}");

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Kampanye Dibuat',
            "Kampanye \"{$campaign->name}\" berhasil dibuat.",
            route('campaigns.index')
        ));

        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil dibuat.');
    }

    public function show(Campaign $campaign)
    {
        $viewModel = new CampaignReport($campaign);
        return view('campaigns.show', ['report'=>$viewModel]);
    }

    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $data = $request->validated();
        unset($data['user_id']);

        $campaign->update($data);

        activity()->performedOn($campaign)->causedBy($request->user())->log("Memperbarui kampanye: {$campaign->name}");

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Kampanye Diperbarui',
            "Kampanye \"{$campaign->name}\" telah diperbarui.",
            route('campaigns.index')
        ));

        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil diperbarui.');
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        $name = $campaign->name;
        $campaign->delete();

        activity()->causedBy($request->user())->log("Menghapus kampanye: {$name}");

        // 📣 notif
        $request->user()?->notify(new GenericDbNotification(
            'Kampanye Dihapus',
            "Kampanye \"{$name}\" telah dihapus.",
            route('campaigns.index')
        ));

        return redirect()->route('campaigns.index')->with('success', 'Kampanye berhasil dihapus.');
    }
}
