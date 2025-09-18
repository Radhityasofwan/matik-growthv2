<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Lead extends Model
{
    use HasFactory, LogsActivity;

    // DB values: trial|active|nonactive|converted|churn
    protected $fillable = [
        'name',
        'email',
        'phone',
        'store_name',
        'status',
        'score',
        'owner_id',
        'trial_ends_at',
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Default trial 7 hari bila trial_ends_at kosong saat membuat.
        static::creating(function (self $lead) {
            if (empty($lead->trial_ends_at)) {
                $lead->trial_ends_at = now()->addDays(7);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name','status','owner_id','store_name','trial_ends_at'])
            ->setDescriptionForEvent(fn (string $e) => "Lead has been {$e}");
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'loggable');
    }
}
