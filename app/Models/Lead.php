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

class Lead extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_TRIAL     = 'trial';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_NONACTIVE = 'nonactive';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CHURN     = 'churn';

    protected $fillable = [
        'name','email','phone','store_name','status','score','owner_id','trial_ends_at',
    ];

    protected $casts = [
        'owner_id'      => 'integer',
        'score'         => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            if (empty($lead->status))       $lead->status = self::STATUS_TRIAL;
            if (empty($lead->trial_ends_at)) $lead->trial_ends_at = now()->addDays(7);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name','status','owner_id','store_name','trial_ends_at'])
            ->setDescriptionForEvent(fn (string $e) => "Lead has been {$e}");
    }

    // ===== Relations =====
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function subscription(): HasOne { return $this->hasOne(Subscription::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function activities(): MorphMany { return $this->morphMany(Activity::class, 'subject'); }

    // ===== Accessors / Mutators =====
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
        return Attribute::make(set: fn (?string $v) => in_array(($v?strtolower(trim($v)):null), $valid, true) ? strtolower(trim($v)) : self::STATUS_TRIAL);
    }

    // ===== Scopes (existing) =====
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $s = '%'.$term.'%';
        return $q->where(fn($qq)=>$qq->where('name','like',$s)->orWhere('email','like',$s)->orWhere('store_name','like',$s));
    }
    public function scopeStatus($q, ?string $status)
    {
        return $status ? $q->where('status', $status) : $q;
    }
    public function scopeOwner($q, ?int $ownerId)
    {
        return $ownerId ? $q->where('owner_id', $ownerId) : $q;
    }

    // ===== Smart Filters for chat buckets =====
    public function scopeChatStatus($q, ?string $bucket)
    {
        if (!$bucket) return $q;

        $now = now();

        if ($bucket === 'no_chat') {
            return $q->whereNotNull('phone')
                ->whereDoesntHave('activities', function ($a) {
                    $a->where('log_name', 'wa_chat');
                });
        }

        // Format: chat_1_no_reply / chat_2_no_reply / chat_3_no_reply
        if (preg_match('~^chat_(\d+)_no_reply$~', $bucket, $m)) {
            $n = (int)$m[1];

            return $q->whereNotNull('phone')
                ->withCount(['activities as wa_chat_count' => function ($a) {
                    $a->where('log_name', 'wa_chat');
                }])
                ->withMax(['activities as last_wa_chat_at' => function ($a) {
                    $a->where('log_name', 'wa_chat');
                }], 'created_at')
                ->withMax(['activities as last_reply_at' => function ($a) {
                    $a->where('log_name', 'lead_reply');
                }], 'created_at')
                ->where('wa_chat_count', '=', $n)
                ->where(function ($qq) {
                    $qq->whereNull('last_reply_at')
                       ->orWhereColumn('last_reply_at', '<', 'last_wa_chat_at');
                });
        }

        return $q;
    }

    // ===== Helper label untuk UI =====
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_TRIAL     => 'Trial',
            self::STATUS_ACTIVE    => 'Aktif',
            self::STATUS_NONACTIVE => 'Tidak Aktif',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_CHURN     => 'Churn',
            default                => ucfirst((string)$this->status),
        };
    }
}
