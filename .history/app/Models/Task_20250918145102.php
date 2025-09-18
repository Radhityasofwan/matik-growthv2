<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date', // Finish At
        'start_at', // Start At
        'assignee_id',
        'creator_id',
        'is_pinned',
        'progress',
        'icon',
        'color',
        'link',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'start_at' => 'datetime',
        'is_pinned' => 'boolean',
    ];

    /** Assigned to */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** Created by */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** Comments */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    /* ===== Scopes ===== */

    /** Tasks dengan due date besok (H-1 reminder) */
    public function scopeDueTomorrow($q)
    {
        $start = now()->addDay()->startOfDay();
        $end   = now()->addDay()->endOfDay();
        return $q->whereNotNull('due_date')->whereBetween('due_date', [$start, $end]);
    }

    /** Tasks yang due kemarin (H+1 overdue) */
    public function scopeOverduePlusOne($q)
    {
        $y = now()->subDay();
        return $q->whereNotNull('due_date')->whereDate('due_date', $y->toDateString());
    }
}
