<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WATemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use App\Jobs\SendWelcomeWA; // We can reuse this job

class BroadcastController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $templates = WATemplate::all();
        $leadStatuses = Lead::select('status')->distinct()->pluck('status');

        return view('whatsapp.broadcast.create', compact('templates', 'leadStatuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:wa_templates,id',
            'segments' => 'required|array|min:1',
            'segments.*' => 'in:trial,active,converted,churn',
        ]);

        $template = WATemplate::findOrFail($request->template_id);
        $leads = Lead::whereIn('status', $request->segments)->get();

        if ($leads->isEmpty()) {
            return redirect()->back()->with('error', 'No leads found for the selected segments.');
        }

        $jobs = [];
        foreach ($leads as $lead) {
            // We can reuse SendWelcomeWA job as it accepts a lead and template name.
            // For a more complex system, a dedicated SendBroadcast job might be better.
            $jobs[] = new SendWelcomeWA($lead, $template->name);
        }

        // Dispatch jobs in a batch for better monitoring if needed
        Bus::batch($jobs)->dispatch();

        return redirect()->route('whatsapp.logs.index')
            ->with('success', "Broadcast to {$leads->count()} leads has been queued successfully.");
    }
}
