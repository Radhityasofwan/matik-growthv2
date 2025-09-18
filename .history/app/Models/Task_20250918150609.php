<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assignee_id',
        'creator_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /* ===== Scopes untuk sweep ===== */

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
