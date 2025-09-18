<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int         $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $store_name
 * @property string      $status
 * @property int|null    $score
 * @property int|null    $owner_id
 * @property int         $chat_count
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User|null         $owner
 * @property-read Subscription|null $subscription
 */
class Lead extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_TRIAL     = 'trial';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_NONACTIVE = 'nonactive';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CHURN     = 'churn';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'store_name',
        'status',
        'score',
        'owner_id',
        'trial_ends_at',
        'chat_count',
    ];

    protected $casts = [
        'owner_id'      => 'integer',
        'score'         => 'integer',
        'chat_count'    => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            if (empty($lead->status)) {
                $lead->status = self::STATUS_TRIAL;
            }
            if (empty($lead->trial_ends_at)) {
                $lead->trial_ends_at = now()->addDays(7);
            }
            if (is_null($lead->chat_count)) {
                $lead->chat_count = 0;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'status',
                'owner_id',
                'store_name',
                'trial_ends_at',
                'chat_count',
            ])
            ->setDescriptionForEvent(fn (string $eventName) => "Lead has been {$eventName}");
    }

    /* ========================= RELATIONS ========================= */

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function subscription(): HasOne { return $this->hasOne(Subscription::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function activities(): MorphMany { return $this->morphMany(Activity::class, 'subject'); }

    /* ====================== ACCESSORS/MUTATORS =================== */

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $v) => $v ? strtolower(trim($v)) : null
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $v) => $v ? preg_replace('/\D+/', '', $v) : null
        );
    }

    protected function status(): Attribute
    {
        $valid = [self::STATUS_TRIAL,self::STATUS_ACTIVE,self::STATUS_NONACTIVE,self::STATUS_CONVERTED,self::STATUS_CHURN];
        return Attribute::make(
            set: fn (?string $v) => in_array($vv = strtolower(trim((string)$v)), $valid, true) ? $vv : self::STATUS_TRIAL
        );
    }

    /* ============================ SCOPES ========================= */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        $s = '%'.$term.'%';
        return $query->where(fn($q)=>$q->where('name','like',$s)->orWhere('email','like',$s)->orWhere('store_name','like',$s));
    }

    public function scopeStatus($query, ?string $status)
    {
        if (!$status) return $query;
        return $query->where('status', $status);
    }

    public function scopeOwner($query, ?int $ownerId)
    {
        if (!$ownerId) return $query;
        return $query->where('owner_id', $ownerId);
    }

    /* ========================= HELPERS =========================== */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_TRIAL     => 'Trial',
            self::STATUS_ACTIVE    => 'Aktif',
            self::STATUS_NONACTIVE => 'Tidak Aktif',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_CHURN     => 'Churn',
            default                => ucfirst((string) $this->status),
        };
    }

    /** Naikkan hitungan chat (dipakai saat WA terkirim / manual chat diklik) */
    public function bumpChatCount(int $step = 1): void
    {
        $this->increment('chat_count', max(1, $step));
        $this->refresh();
    }
}
