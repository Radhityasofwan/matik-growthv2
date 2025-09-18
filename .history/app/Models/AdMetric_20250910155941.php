<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'campaign_id',
        'date',
        'impressions',
        'clicks',
        'spend',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the ad account that owns the metric.
     */
    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
}
