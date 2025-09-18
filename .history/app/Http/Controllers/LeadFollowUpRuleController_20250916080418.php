<?php

namespace App\Http\Controllers;

use App\Models\LeadFollowUpRule;
use App\Models\Lead;
use App\Models\WATemplate;
use App\Models\WahaSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LeadFollowUpRuleController extends Controller
{
    public function index()
    {
        $rules = LeadFollowUpRule::with(['lead','template','sender','creator','updater'])
            ->latest()->paginate(20);

        return view('leads.rules.index', compact('rules'));
    }

    public function create()
    {
        $leads     = Lead::orderBy('name')->get(['id','name','email','phone']);
        $senders   = WahaSender::orderByDesc('is_default')->orderBy('name')->get(['id','name','number','is_default']);

        // Aman jika tabel template belum ada
        $templates = Schema::hasTable('w_a_templates')
            ? WATemplate::orderBy('name')->get(['id','name'])
            : collect();

        return view('leads.rules.create', [
            'leads'      => $leads,
            'templates'  => $templates,
            'senders'    => $senders,
            'conditions' => LeadFollowUpRule::conditions(),
        ]);
    }

    public function store(Request $request)
    {
        $baseRules = [
            'lead_id'        => ['nullable','integer','exists:leads,id'],
            'condition'      => ['required','string','max:50', Rule::in(LeadFollowUpRule::conditions())],
            'days_after'     => ['required','integer','min:1','max:365'],
            'waha_sender_id' => ['nullable','integer','exists:waha_senders,id'],
            'is_active'      => ['sometimes','boolean'],
        ];

        // validasi template hanya jika tabelnya ada
        if (Schema::hasTable('w_a_templates')) {
            $baseRules['wa_template_id'] = ['nullable','integer','exists:w_a_templates,id'];
        } else {
            $baseRules['wa_template_id'] = ['nullable','integer'];
        }

        $data = $request->validate($baseRules);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        LeadFollowUpRule::create($data);

        return redirect()->route('lead-follow-up-rules.index')->with('success','Rule berhasil ditambahkan.');
    }

    public function edit(LeadFollowUpRule $leadFollowUpRule)
    {
        $leads     = Lead::orderBy('name')->get(['id','name','email','phone']);
        $senders   = WahaSender::orderByDesc('is_default')->orderBy('name')->get(['id','name','number','is_default']);
        $templates = Schema::hasTable('w_a_templates')
            ? WATemplate::orderBy('name')->get(['id','name'])
            : collect();

        return view('leads.rules.edit', [
            'rule'       => $leadFollowUpRule,
            'leads'      => $leads,
            'templates'  => $templates,
            'senders'    => $senders,
            'conditions' => LeadFollowUpRule::conditions(),
        ]);
    }

    public function update(Request $request, LeadFollowUpRule $leadFollowUpRule)
    {
        $baseRules = [
            'lead_id'        => ['nullable','integer','exists:leads,id'],
            'condition'      => ['required','string','max:50', Rule::in(LeadFollowUpRule::conditions())],
            'days_after'     => ['required','integer','min:1','max:365'],
            'waha_sender_id' => ['nullable','integer','exists:waha_senders,id'],
            'is_active'      => ['sometimes','boolean'],
        ];

        if (Schema::hasTable('w_a_templates')) {
            $baseRules['wa_template_id'] = ['nullable','integer','exists:w_a_templates,id'];
        } else {
            $baseRules['wa_template_id'] = ['nullable','integer'];
        }

        $data = $request->validate($baseRules);
        $data['updated_by'] = Auth::id();

        $leadFollowUpRule->update($data);

        return redirect()->route('lead-follow-up-rules.index')->with('success','Rule berhasil diperbarui.');
    }

    public function destroy(LeadFollowUpRule $leadFollowUpRule)
    {
        $leadFollowUpRule->delete();

        return redirect()->route('lead-follow-up-rules.index')->with('success','Rule berhasil dihapus.');
    }
}
