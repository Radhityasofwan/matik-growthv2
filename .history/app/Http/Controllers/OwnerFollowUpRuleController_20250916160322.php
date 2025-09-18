<?php

namespace App\Http\Controllers;

use App\Models\OwnerFollowUpRule;
use Illuminate\Http\Request;

class OwnerFollowUpRuleController extends Controller
{
    public function index() {
        return view('owner_follow_up_rules.index', [
            'rules' => OwnerFollowUpRule::query()->orderBy('id','desc')->get(),
            // kirim juga templates & senders utk dropdown
        ]);
    }

    public function store(Request $r) {
        $data = $r->validate([
            'lead_id'     => ['nullable','exists:leads,id'],
            'condition'   => ['required','in:no_chat,chat_1_no_reply,chat_2_no_reply,chat_3_no_reply'],
            'days_after'  => ['required','integer','min:0'],
            'template_id' => ['nullable','exists:wa_templates,id'],
            'sender_id'   => ['nullable','exists:waha_senders,id'],
            'is_active'   => ['required','boolean'],
        ]);
        OwnerFollowUpRule::create($data);
        return back()->with('success','Rule owner dibuat.');
    }

    public function update(Request $r, OwnerFollowUpRule $ownerFollowUpRule) {
        $data = $r->validate([
            'lead_id'     => ['nullable','exists:leads,id'],
            'condition'   => ['required','in:no_chat,chat_1_no_reply,chat_2_no_reply,chat_3_no_reply'],
            'days_after'  => ['required','integer','min:0'],
            'template_id' => ['nullable','exists:wa_templates,id'],
            'sender_id'   => ['nullable','exists:waha_senders,id'],
            'is_active'   => ['required','boolean'],
        ]);
        $ownerFollowUpRule->update($data);
        return back()->with('success','Rule owner diperbarui.');
    }

    public function destroy(OwnerFollowUpRule $ownerFollowUpRule) {
        $ownerFollowUpRule->delete();
        return back()->with('success','Rule owner dihapus.');
    }
}
