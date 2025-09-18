<?php

namespace App\Http\Controllers;

use App\Models\LeadFollowUpRule;
use App\Models\Lead;
use App\Models\WATemplate;
use App\Models\WahaSender;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadFollowUpRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = LeadFollowUpRule::with(['lead','template','sender','creator'])
            ->orderByDesc('id')->paginate(20);

        return view('leads.rules.index', [
            'rules'      => $rules,
            'leads'      => Lead::orderBy('name')->limit(200)->get(['id','name','store_name','email']),
            'templates'  => WATemplate::orderBy('name')->get(['id','name']),
            'senders'    => WahaSender::orderByDesc('is_default')->orderBy('name')->get(['id','name','number','is_default']),
            'conditions' => LeadFollowUpRule::conditions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id'        => ['nullable','exists:leads,id'],
            'condition'      => ['required', Rule::in(LeadFollowUpRule::conditions())],
            'days_after'     => ['required','integer','min:1','max:365'],
            'wa_template_id' => ['nullable','exists:w_a_templates,id'],
            'waha_sender_id' => ['nullable','exists:waha_senders,id'],
            'is_active'      => ['sometimes','boolean'],
        ]);

        $data['created_by'] = $request->user()?->id;
        $data['is_active']  = (bool)($data['is_active'] ?? true);

        LeadFollowUpRule::create($data);

        return back()->with('success', 'Aturan follow-up berhasil ditambahkan.');
    }

    public function update(Request $request, LeadFollowUpRule $leadFollowUpRule)
    {
        $data = $request->validate([
            'lead_id'        => ['nullable','exists:leads,id'],
            'condition'      => ['required', Rule::in(LeadFollowUpRule::conditions())],
            'days_after'     => ['required','integer','min:1','max:365'],
            'wa_template_id' => ['nullable','exists:w_a_templates,id'],
            'waha_sender_id' => ['nullable','exists:waha_senders,id'],
            'is_active'      => ['sometimes','boolean'],
        ]);

        $data['updated_by'] = $request->user()?->id;
        $data['is_active']  = (bool)($data['is_active'] ?? true);

        $leadFollowUpRule->update($data);

        return back()->with('success', 'Aturan follow-up berhasil diperbarui.');
    }

    public function destroy(LeadFollowUpRule $leadFollowUpRule)
    {
        $leadFollowUpRule->delete();
        return back()->with('success', 'Aturan follow-up dihapus.');
    }
}
