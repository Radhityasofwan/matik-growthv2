<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $lead_id
 * @property string      $plan
 * @property string      $cycle    monthly|yearly
 * @property string      $status   active|paused|canceled|expired
 * @property string      $amount
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 *
 * @property-read Lead   $lead
 */
class Subscription extends Model
{
    use HasFactory;

    /** Cycles */
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_YEARLY  = 'yearly';

    /** Statuses */
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_PAUSED   = 'paused';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED  = 'expired';

    /** Mass-assignable columns */
    protected $fillable = [
        'lead_id',
        'plan',
        'amount',
        'cycle',
        'status',
        'start_date',
        'end_date',
    ];

    /** Casts */
    protected $casts = [
        'lead_id'    => 'integer',
        'amount'     => 'decimal:2',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * Defaults & guards
     * - status default: active
     * - normalisasi sederhana saat membuat
     */
    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            if (empty($sub->status)) {
                $sub->status = self::STATUS_ACTIVE;
            }
            // Jika start_date kosong tetapi end_date ada, set start_date = today untuk konsistensi minimal
            if (empty($sub->start_date) && !empty($sub->end_date)) {
                $sub->start_date = now()->startOfDay();
            }
        });
    }

    /* =========================
     *         RELATIONS
     * ========================= */

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /* =========================
     *    ACCESSORS / MUTATORS
     * ========================= */

    /** Normalisasi cycle ke nilai yang valid (monthly|yearly) */
    protected function cycle(): Attribute
    {
        $valid = [self::CYCLE_MONTHLY, self::CYCLE_YEARLY];

        return Attribute::make(
            set: function (?string $value) use ($valid) {
                $v = $value ? strtolower(trim($value)) : null;
                return in_array($v, $valid, true) ? $v : self::CYCLE_MONTHLY;
            }
        );
    }

    /** Normalisasi status ke daftar yang diizinkan, default active */
    protected function status(): Attribute
    {
        $valid = [self::STATUS_ACTIVE, self::STATUS_PAUSED, self::STATUS_CANCELED, self::STATUS_EXPIRED];

        return Attribute::make(
            set: function (?string $value) use ($valid) {
                $v = $value ? strtolower(trim($value)) : null;
                return in_array($v, $valid, true) ? $v : self::STATUS_ACTIVE;
            }
        );
    }

    /** Pastikan amount tidak negatif */
    protected function amount(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => max(0, (float) $value)
        );
    }

    /* =========================
     *          SCOPES
     * ========================= */

    /** Scope berlangganan aktif (status active & dalam rentang tanggal jika ada) */
    public function scopeCurrent($query)
    {
        $today = now()->startOfDay();
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
    }

    /** Scope untuk lead tertentu */
    public function scopeForLead($query, int $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    /* =========================
     *       HELPER ATTRS
     * ========================= */

    /** Apakah subscription ini sedang aktif pada hari ini */
    public function getIsCurrentAttribute(): bool
    {
        $today = now()->startOfDay();

        $withinStart = $this->start_date ? $this->start_date->lte($today) : true;
        $withinEnd   = $this->end_date ? $this->end_date->gte($today) : true;

        return $this->status === self::STATUS_ACTIVE && $withinStart && $withinEnd;
    }
}
