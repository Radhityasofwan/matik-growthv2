<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\CausesActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, CausesActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',        // path relatif di disk 'public' atau URL penuh
        'last_login_at',
        'is_active',
        'wa_number',     // nomor WhatsApp aktif untuk notifikasi internal
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
        ];
    }

    /** Otomatis ikutkan avatar_url saat toArray()/JSON */
    protected $appends = ['avatar_url'];

    /** Accessor URL avatar siap pakai */
    public function getAvatarUrlAttribute(): string
    {
        $avatar = $this->avatar;

        // 1) Jika sudah URL penuh
        if (is_string($avatar) && (Str::startsWith($avatar, 'http://') || Str::startsWith($avatar, 'https://'))) {
            return $avatar;
        }

        // 2) Jika path relatif & file ada di disk 'public'
        if (is_string($avatar) && $avatar !== '' && Storage::disk('public')->exists($avatar)) {
            return Storage::disk('public')->url($avatar);
        }

        // 3) Fallback UI-Avatars
        $bg   = '1F2937'; // slate-800
        $fg   = 'ffffff';
        $name = urlencode($this->name ?: 'User');

        return "https://ui-avatars.com/api/?name={$name}&background={$bg}&color={$fg}&format=png";
    }

    /** Accessor nomor WhatsApp (selalu hanya digit) */
    public function getWaNumberAttribute(?string $value): ?string
    {
        if (!$value) return null;
        return preg_replace('/\D+/', '', $value);
    }

    /** Mutator nomor WhatsApp (sanitize otomatis) */
    public function setWaNumberAttribute(?string $value): void
    {
        $this->attributes['wa_number'] = $value
            ? preg_replace('/\D+/', '', $value)
            : null;
    }
}
