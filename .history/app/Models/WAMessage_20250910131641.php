<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WAMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'wa_template_id',
        'phone_number',
        'message',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WATemplate::class, 'wa_template_id');
    }
}
