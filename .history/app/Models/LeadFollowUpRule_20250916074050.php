<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadFollowUpRule extends Model
{
    use HasFactory;

    protected $table = 'lead_follow_up_rules';

    protected $fillable = [
        'lead_id',
        'condition',
        'days_after',
        'wa_template_id',
        'waha_sender_id',
        'is_active',
        'created_by',
        'updated_by',
        'last_run_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'last_run_at'=> 'datetime',
    ];

    // ==== Relations ====
    public function lead() { return $this->belongsTo(Lead::class); }

    // Asumsi model WATemplate ada di App\Models\WATemplate
    public function template() { return $this->belongsTo(WATemplate::class, 'wa_template_id'); }

    // Sender WAHA
    public function sender() { return $this->belongsTo(WahaSender::class, 'waha_sender_id'); }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    // ==== Helpers ====
    public function scopeActive($q) { return $q->where('is_active', true); }

    public static function conditions(): array
    {
        return [
            'no_chat',
            'chat_1_no_reply',
            'chat_2_no_reply',
            'chat_3_no_reply',
        ];
    }
}
