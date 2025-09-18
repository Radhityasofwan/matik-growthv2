<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'url',
        'unique_id',
        'clicks',
    ];

    /**
     * Get the campaign that owns the link.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
