<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OwnerFollowUpRule extends Model
{
    protected $table = 'owner_follow_up_rules';

    /** @var array<string> */
    public const TRIGGERS = [
        'on_send', 'on_success', 'on_fail',
        'on_trial_ends_at', 'on_due_at',
    ];

    protected $fillable = [
        'lead_id',
        'trigger',
        'days_before',
        'template_id',
        'sender_id',
        'is_active',
        'last_run_at',
    ];

    protected $casts = [
        'is_active'  => 'bool',
        'last_run_at'=> 'datetime',
        'days_before'=> 'int',
    ];

    /* ===================== Relationships ===================== */

    public function lead() { return $this->belongsTo(Lead::class); }

    // Gunakan model template WA yang kamu pakai. Jika namanya "WATemplate", sesuaikan import di atas.
    public function template() { return $this->belongsTo(WATemplate::class, 'template_id'); }

    public function sender() { return $this->belongsTo(WahaSender::class, 'sender_id'); }

    /* ======================== Scopes ========================= */

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /** Ambil rule paling relevan: khusus lead (jika ada) lalu global */
    public function scopeForLeadOrGlobal(Builder $q, Lead $lead): Builder
    {
        return $q->where(function (Builder $w) use ($lead) {
            $w->whereNull('lead_id')->orWhere('lead_id', $lead->id);
        })
        ->orderByRaw('lead_id is not null desc')
        ->orderBy('id');
    }

    /** Filter trigger */
    public function scopeTriggeredBy(Builder $q, string|array $triggers): Builder
    {
        $arr = is_array($triggers) ? $triggers : [$triggers];
        return $q->whereIn('trigger', $arr);
    }

    /* ===================== Small Helpers ===================== */

    public function requiresDaysBefore(): bool
    {
        return in_array($this->trigger, ['on_trial_ends_at','on_due_at'], true);
    }
}
