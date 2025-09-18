<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Lead extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'status',
        'score',
        'owner_id', // Foreign key should ideally match the relationship name
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * --- INI PERBAIKANNYA ---
     * Mengubah nama method dari 'user' menjadi 'owner' agar sesuai
     * dengan pemanggilan di controller (->with('owner')).
     *
     * Laravel akan secara otomatis mencari foreign key 'owner_id'.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
