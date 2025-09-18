<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadFollowUpRule;
use App\Models\WATemplate;
use App\Models\WahaSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LeadFollowUpRuleController extends Controller
{
    public function index()
    {
        $rules = LeadFollowUpRule::with(['lead', 'template', 'sender'])
            ->orderByRaw('COALESCE(lead_id, 0) ASC')
            ->latest('id')
            ->get();

        // Dropdown "Per Lead"
        $leads = Lead::select('id', 'name', 'store_name', 'email')
            ->orderBy('name')
            ->limit(500)
            ->get();

        // FIX: pakai nama tabel yang benar: wa_templates
        // plus try-catch supaya aman kalau tabel belum ada.
        if (Schema::hasTable('wa_templates')) {
            try {
                $templates = WATemplate::orderBy('name')->get(['id', 'name']);
            } catch (\Throwable $e) {
                $templates = collect();
            }
        } else {
            $templates = collect();
        }

        // Sender WAHA (aman bila tabel belum ada)
        if (Schema::hasTable('waha_senders')) {
            try {
                $senders = WahaSender::orderBy('name')->get(['id', 'name', 'number']);
            } catch (\Throwable $e) {
                $senders = collect();
            }
        } else {
            $senders = collect();
        }

        return view('sales.leads.rules.index', [
            'rules'      => $rules,
            'leads'      => $leads,
            'senders'    => $senders,
            'templates'  => $templates,
            'conditions' => LeadFollowUpRule::conditions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id'        => ['nullable', 'integer', 'exists:leads,id'],
            'condition'      => ['required', 'string', 'in:'.implode(',', LeadFollowUpRule::conditions())],
            'days_after'     => ['required', 'integer', 'min:0', 'max:365'],
            'wa_template_id' => ['nullable', 'integer'],
            'waha_sender_id' => ['nullable', 'integer'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $data['is_active']  = (bool)($data['is_active'] ?? true);
        $data['created_by'] = Auth::id();

        LeadFollowUpRule::create($data);

        return redirect()->route('lead-follow-up-rules.index')->with('success', 'Aturan berhasil dibuat.');
    }

    public function update(Request $request, LeadFollowUpRule $lead_follow_up_rule)
    {
        $data = $request->validate([
            'lead_id'        => ['nullable', 'integer', 'exists:leads,id'],
            'condition'      => ['required', 'string', 'in:'.implode(',', LeadFollowUpRule::conditions())],
            'days_after'     => ['required', 'integer', 'min:0', 'max:365'],
            'wa_template_id' => ['nullable', 'integer'],
            'waha_sender_id' => ['nullable', 'integer'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $data['is_active']  = (bool)($data['is_active'] ?? false);
        $data['updated_by'] = Auth::id();

        $lead_follow_up_rule->update($data);

        return redirect()->route('lead-follow-up-rules.index')->with('success', 'Aturan berhasil diperbarui.');
    }

    public function destroy(LeadFollowUpRule $lead_follow_up_rule)
    {
        $lead_follow_up_rule->delete();

        return redirect()->route('lead-follow-up-rules.index')->with('success', 'Aturan dihapus.');
    }
}
