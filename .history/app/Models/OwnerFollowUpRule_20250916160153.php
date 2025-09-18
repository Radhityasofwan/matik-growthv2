<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OwnerFollowUpRule extends Model
{
    protected $fillable = [
        'lead_id','condition','days_after','template_id','sender_id','is_active','last_run_at'
    ];
    protected $casts = ['is_active'=>'bool','days_after'=>'int','last_run_at'=>'datetime'];

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }

    public function lead()     { return $this->belongsTo(Lead::class); }
    public function template() { return $this->belongsTo(WATemplate::class, 'template_id'); }
    public function sender()   { return $this->belongsTo(WahaSender::class, 'sender_id'); }
}
