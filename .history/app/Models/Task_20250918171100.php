<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Task extends Model
{
    use HasFactory;

    /**
     * Nama tabel (opsional—hapus jika konvensi sudah 'tasks').
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * Kolom yang boleh di-mass-assign.
     *
     * Pastikan daftar ini mencakup semua field yang digunakan
     * oleh controller/forms Anda saat create/update.
     *
     * @var array<int, string>
     */
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

        // Warna tetap diizinkan jika kolom ada di DB, meskipun input UI disembunyikan
        'color',

    ];

    /**
     * Casting kolom.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_at'  => 'datetime',
        'due_date'  => 'datetime',
        'progress'  => 'integer',
        'owner_ids' => 'array',   // disimpan sebagai JSON array
    ];

    /* ============================================================
     |  RELATIONS
     |============================================================*/

    /**
     * PIC utama (assignee tunggal).
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Pembuat tugas (owner pembuat).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relasi opsional untuk banyak owner melalui pivot.
     *
     * Gunakan jika Anda memiliki tabel pivot, mis: `task_owners`
     * dengan kolom: task_id, user_id.
     *
     * Jika tabel tidak ada, pemanggilan ini dibungkus try/catch
     * di Job sehingga aman (silent fail).
     */
    public function owners()
    {
        // Ubah nama tabel pivot bila berbeda
        return $this->belongsToMany(User::class, 'task_owners', 'task_id', 'user_id')
                    ->withTimestamps();
    }

    /* ============================================================
     |  ACCESSORS / HELPERS
     |============================================================*/

    /**
     * Helper sederhana: apakah sudah lewat due_date dan belum 'done'.
     */
    public function getIsOverdueAttribute(): bool
    {
        /** @var Carbon|null $due */
        $due = $this->due_date instanceof Carbon ? $this->due_date : null;

        return $due?->isPast() && $this->status !== 'done';
    }

    /**
     * Normalisasi prioritas ke salah satu nilai yang diharapkan.
     * (Dipakai jika Anda ingin menjaga konsistensi data.)
     */
    public function setPriorityAttribute($value): void
    {
        $val = strtolower((string) $value);
        $allowed = ['low', 'medium', 'high', 'urgent'];
        $this->attributes['priority'] = in_array($val, $allowed, true) ? $val : 'medium';
    }

    /**
     * Normalisasi status ke salah satu nilai yang diharapkan.
     */
    public function setStatusAttribute($value): void
    {
        $val = (string) $value;
        $allowed = ['open', 'in_progress', 'review', 'done'];
        $this->attributes['status'] = in_array($val, $allowed, true) ? $val : 'open';
    }
}
