<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    /**
     * Kolom mass-assign.
     */
    protected $fillable = [
        'title',
        'description',
        'link',

        'assignee_id',
        'creator_id',

        'priority',    // low|medium|high|urgent
        'status',      // open|in_progress|review|done|overdue
        'progress',    // 0..100 (opsional)

        'start_at',
        'due_date',

        'color',

        // ketika form mengirim array id owner (disimpan di pivot &/atau JSON)
        'owner_ids',
    ];

    /**
     * Casting kolom.
     */
    protected $casts = [
        'start_at'  => 'datetime',
        'due_date'  => 'datetime',
        'progress'  => 'integer',
        'owner_ids' => 'array',   // JSON array jika kolom tersedia
    ];

    /* ============================================================
     |  RELATIONS
     |============================================================*/

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Banyak owner melalui pivot `task_owners` (task_id, user_id).
     */
    public function owners()
    {
        return $this->belongsToMany(User::class, 'task_owners', 'task_id', 'user_id')
                    ->withTimestamps();
    }

    /* ============================================================
     |  ACCESSORS / HELPERS
     |============================================================*/

    public function getIsOverdueAttribute(): bool
    {
        /** @var Carbon|null $due */
        $due = $this->due_date instanceof Carbon ? $this->due_date : null;

        return $due?->isPast() && $this->status !== 'done';
    }

    public function setPriorityAttribute($value): void
    {
        $val = strtolower((string) $value);
        $allowed = ['low', 'medium', 'high', 'urgent'];
        $this->attributes['priority'] = in_array($val, $allowed, true) ? $val : 'medium';
    }

    public function setStatusAttribute($value): void
    {
        $val = (string) $value;
        $allowed = ['open', 'in_progress', 'review', 'done', 'overdue']; // ⬅ sinkron dengan Command + Request
        $this->attributes['status'] = in_array($val, $allowed, true) ? $val : 'open';
    }

    /* ============================================================
     |  SCOPES untuk cron/command H-1 & H+1
     |============================================================*/

    /**
     * Tugas yang jatuh tempo BESOK (H-1), belum selesai.
     */
    public function scopeDueTomorrow($q)
    {
        $start = now()->startOfDay()->addDay();
        $end   = now()->endOfDay()->addDay();
        return $q->whereBetween('due_date', [$start, $end])
                 ->where('status', '!=', 'done');
    }

    /**
     * Tugas yang sudah lewat 1 hari (H+1) dari due_date, belum selesai.
     */
    public function scopeOverduePlusOne($q)
    {
        $h1End = now()->endOfDay()->subDay(); // kemarin 23:59
        $h1Start = now()->startOfDay()->subDays(2); // dua hari lalu 00:00
        // Ambil estimasi H+1 (range longgar agar aman terhadap timezone/cron)
        return $q->whereBetween('due_date', [$h1Start, $h1End])
                 ->where('due_date', '<', now())
                 ->where('status', '!=', 'done');
    }
}
