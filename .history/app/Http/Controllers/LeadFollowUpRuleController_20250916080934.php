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
    /**
     * Tampilkan daftar rule + data pendukung untuk form create/edit (modal).
     */
    public function index(Request $request)
    {
        $rules = LeadFollowUpRule::with(['lead','template','sender','creator','updater'])
            ->latest()->paginate(20)->withQueryString();

        // Data untuk dropdown di modal
        $leads   = Lead::orderBy('name')->get(['id','name','email','phone']);
        $senders = WahaSender::orderByDesc('is_default')->orderBy('name')->get(['id','name','number','is_default']);

        // Aman jika tabel template belum ada
        $templates = Schema::hasTable('w_a_templates')
            ? WATemplate::orderBy('name')->get(['id','name'])
            : collect();

        return view('leads.rules.index', [
            'rules'      => $rules,
            'leads'      => $leads,
            'senders'    => $senders,
            'templates'  => $templates,
            'conditions' => LeadFollowUpRule::conditions(),
        ]);
    }

    /**
     * Simpan rule baru (dipanggil dari modal di index).
     */
    public function store(Request $request)
    {
        $baseRules = [
            'lead_id'        => ['nullable','integer','exists:leads,id'],
            'condition'      => ['required','string','max:50', Rule::in(LeadFollowUpRule::conditions())],
            'days_after'     => ['required','integer','min:1','max:365'],
            'waha_sender_id' => ['nullable','integer','exists:waha_senders,id'],
            'is_active'      => ['sometimes','boolean'],
        ];
        // Validasi template hanya jika tabelnya ada
        $baseRules['wa_template_id'] = Schema::hasTable('w_a_templates')
            ? ['nullable','integer','exists:w_a_templates,id']
            : ['nullable','integer'];

        $data = $request->validate($baseRules);
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        LeadFollowUpRule::create($data);

        return redirect()->route('lead-follow-up-rules.index')
            ->with('success','Rule follow-up berhasil ditambahkan.');
    }

    /**
     * Update rule (dipanggil dari modal edit di index).
     */
    public function update(Request $request, LeadFollowUpRule $leadFollowUpRule)
    {
        $baseRules = [
            'lead_id'        => ['nullable','integer','exists:leads,id'],
            'condition'      => ['required','string','max:50', Rule::in(LeadFollowUpRule::conditions())],
            'days_after'     => ['required','integer','min:1','max:365'],
            'waha_sender_id' => ['nullable','integer','exists:waha_senders,id'],
            'is_active'      => ['sometimes','boolean'],
        ];
        $baseRules['wa_template_id'] = Schema::hasTable('w_a_templates')
            ? ['nullable','integer','exists:w_a_templates,id']
            : ['nullable','integer'];

        $data = $request->validate($baseRules);
        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $data['updated_by'] = Auth::id();

        $leadFollowUpRule->update($data);

        return redirect()->route('lead-follow-up-rules.index')
            ->with('success','Rule follow-up berhasil diperbarui.');
    }

    /**
     * Hapus rule.
     */
    public function destroy(LeadFollowUpRule $leadFollowUpRule)
    {
        $leadFollowUpRule->delete();

        return redirect()->route('lead-follow-up-rules.index')
            ->with('success','Rule follow-up berhasil dihapus.');
    }
}
