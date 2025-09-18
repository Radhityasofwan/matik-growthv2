<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'account_id',
        'name',
        'access_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the metrics for the ad account.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }
}
