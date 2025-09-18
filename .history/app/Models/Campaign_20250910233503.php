<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'channel',
        'status',
        'budget',
        'revenue',
        'start_date',
        'end_date',
        'owner_id',
        'user_id',      // penting: izinkan mass-assignment
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'budget'     => 'decimal:2',
        'revenue'    => 'decimal:2',
    ];

    // Set otomatis user_id saat create jika belum diisi
    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (blank($campaign->user_id) && auth()->check()) {
                $campaign->user_id = auth()->id();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // relasi pembuat (opsional tapi berguna)
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
