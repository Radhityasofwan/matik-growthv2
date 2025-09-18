<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        'title',
        'description',
        'link',
        'assignee_id',
        'creator_id',
        'priority',   // low|medium|high|urgent
        'status',     // open|in_progress|review|done
        'progress',   // 0..100 (opsional)
        'start_at',
        'due_date',
        'color',
        // owner_ids TIDAK dimasukkan ke fillable karena disimpan via relasi pivot owners()
    ];

    protected $casts = [
        'start_at'  => 'datetime',
        'due_date'  => 'datetime',
        'progress'  => 'integer',
        'owner_ids' => 'array', // optional (jika ada kolom JSON); aman meski tidak ada, karena tidak di-mass assign
    ];

    /* =================== RELATIONS =================== */

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function owners()
    {
        return $this->belongsToMany(User::class, 'task_owners', 'task_id', 'user_id')->withTimestamps();
    }

    /* =================== ACCESSORS =================== */

    public function getIsOverdueAttribute(): bool
    {
        /** @var Carbon|null $due */
        $due = $this->due_date instanceof Carbon ? $this->due_date : null;
        return (bool) ($due?->isPast() && $this->status !== 'done');
    }

    /* =================== MUTATORS =================== */

    public function setPriorityAttribute($value): void
    {
        $val = strtolower((string) $value);
        $allowed = ['low', 'medium', 'high', 'urgent'];
        $this->attributes['priority'] = in_array($val, $allowed, true) ? $val : 'medium';
    }

    public function setStatusAttribute($value): void
    {
        $val = (string) $value;
        $allowed = ['open', 'in_progress', 'review', 'done']; // sinkron dengan kolom-kanban: to do, in progress, preview, done
        $this->attributes['status'] = in_array($val, $allowed, true) ? $val : 'open';
    }

    /* =================== SCOPES =================== */

    /** Tugas dengan due date besok (H-1) */
    public function scopeDueTomorrow($q)
    {
        $tz = config('app.timezone');
        $start = Carbon::now($tz)->addDay()->startOfDay()->utc();
        $end   = Carbon::now($tz)->addDay()->endOfDay()->utc();
        return $q->whereBetween('due_date', [$start, $end]);
    }

    /**
     * Tugas yang sudah lewat 1 hari dari due (H+1).
     * Tidak memaksa status 'overdue' agar tidak mengganggu kolom kanban.
     */
    public function scopeOverduePlusOne($q)
    {
        $tz = config('app.timezone');
        $cutoff = Carbon::now($tz)->subDay()->endOfDay()->utc();
        return $q->where('due_date', '<', $cutoff);
    }
}
