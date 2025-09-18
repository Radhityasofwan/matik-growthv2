<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\CausesActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /**
     * Menambahkan trait CausesActivity agar model ini bisa
     * dicatat sebagai penyebab sebuah aktivitas.
     */
    use HasFactory, Notifiable, CausesActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',        // simpan path relatif (public) atau URL penuh
        'last_login_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Otomatis ikutkan avatar_url saat toArray()/JSON.
     *
     * @var array<int, string>
     */
    protected $appends = ['avatar_url'];

    /**
     * Accessor: URL avatar siap pakai untuk <img>.
     */
    public function getAvatarUrlAttribute(): string
    {
        $avatar = $this->avatar;

        // 1) Jika sudah URL penuh
        if (is_string($avatar) && (Str::startsWith($avatar, 'http://') || Str::startsWith($avatar, 'https://'))) {
            return $avatar;
        }

        // 2) Path relatif di disk 'public'
        if (is_string($avatar) && $avatar !== '' && Storage::disk('public')->exists($avatar)) {
            return Storage::disk('public')->url($avatar);
        }

        // 3) Fallback UI-Avatars
        $bg   = '1F2937'; // slate-800
        $fg   = 'ffffff';
        $name = urlencode($this->name ?: 'User');
        return "https://ui-avatars.com/api/?name={$name}&background={$bg}&color={$fg}&format=png";
    }
}
