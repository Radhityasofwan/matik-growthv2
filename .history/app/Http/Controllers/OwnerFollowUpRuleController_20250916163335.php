<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\OwnerFollowUpRule;
use App\Models\WahaSender;
use App\Models\WATemplate; // ganti jika model template-nya beda
use Illuminate\Http\Request;

class OwnerFollowUpRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = OwnerFollowUpRule::query()
            ->with(['lead:id,name,email', 'template:id,name', 'sender:id,name,number,is_default'])
            ->orderByDesc('id')
            ->paginate(20);

        $leads     = Lead::query()->select('id','name','email')->orderBy('name')->limit(200)->get();
        $templates = WATemplate::query()->select('id','name')->orderBy('name')->get();
        $senders   = WahaSender::query()->orderByDesc('is_default')->orderBy('id')->get();
        $triggers  = OwnerFollowUpRule::TRIGGERS;

        if ($request->wantsJson()) {
            return response()->json(compact('rules','leads','templates','senders','triggers'));
        }

        // Jika kamu pakai Blade khusus, ganti nama view berikut
        return view('owner_follow_up_rules.index', compact('rules','leads','templates','senders','triggers'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $rule = OwnerFollowUpRule::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'rule' => $rule->load(['lead','template','sender'])]);
        }

        return back()->with('success', 'Owner follow-up rule berhasil dibuat.');
    }

    public function update(Request $request, OwnerFollowUpRule $owner_follow_up_rule)
    {
        $data = $this->validatedData($request);

        $owner_follow_up_rule->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'rule' => $owner_follow_up_rule->fresh()->load(['lead','template','sender'])]);
        }

        return back()->with('success', 'Owner follow-up rule berhasil diperbarui.');
    }

    public function destroy(Request $request, OwnerFollowUpRule $owner_follow_up_rule)
    {
        $owner_follow_up_rule->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Owner follow-up rule dihapus.');
    }

    /* ========================= Utils ========================= */

    protected function validatedData(Request $request): array
    {
        $triggers = implode(',', OwnerFollowUpRule::TRIGGERS);

        $base = $request->validate([
            'lead_id'      => ['nullable','integer','exists:leads,id'],
            'trigger'      => ['required',"in:{$triggers}"],
            'days_before'  => ['nullable','integer','min:0','max:365'],
            'template_id'  => ['nullable','integer','exists:wa_templates,id'],
            'sender_id'    => ['nullable','integer','exists:waha_senders,id'],
            'is_active'    => ['sometimes','boolean'],
        ]);

        // Normalisasi boolean
        $base['is_active'] = (bool) ($base['is_active'] ?? true);

        // days_before wajib untuk trigger tanggal; harus null untuk trigger proses
        $dateTriggers = ['on_trial_ends_at','on_due_at'];
        if (in_array($base['trigger'], $dateTriggers, true)) {
            if (!array_key_exists('days_before', $base) || $base['days_before'] === null) {
                abort(422, 'days_before diperlukan untuk trigger bertipe tanggal.');
            }
        } else {
            $base['days_before'] = null;
        }

        return $base;
    }
}
