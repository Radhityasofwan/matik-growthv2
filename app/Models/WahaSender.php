<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Schema;

class WahaSender extends Model
{
    use HasFactory;

    protected $table = 'waha_senders';

    protected $fillable = [
        'name',
        'description',
        'display_name',   // ikut diisi ke DB
        'session',
        'session_name',
        'number',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    /* -----------------------------------------------------------------
     |  Query Scopes
     |------------------------------------------------------------------
     |  Gunakan di controller:
     |    WahaSender::connected()->count();
     |    WahaSender::default()->first();
     |    WahaSender::search($q)->paginate(20);
     |------------------------------------------------------------------ */

    /**
     * Sender yang terhubung/aktif.
     * Otomatis memilih kolom yang tersedia (aman untuk variasi skema).
     */
    public function scopeConnected($query)
    {
        $table = $this->getTable();

        if (Schema::hasColumn($table, 'status')) {
            // Jika suatu saat ada kolom string 'status'
            return $query->where('status', 'connected');
        }

        if (Schema::hasColumn($table, 'connection_status')) {
            return $query->where('connection_status', 'connected');
        }

        if (Schema::hasColumn($table, 'is_connected')) {
            return $query->where('is_connected', 1);
        }

        if (Schema::hasColumn($table, 'is_active')) {
            // Skema yang kamu pakai saat ini
            return $query->where('is_active', 1);
        }

        // Tidak ada kolom relevan → hasil kosong (hindari error)
        return $query->whereRaw('1 = 0');
    }

    /**
     * Alias dari connected().
     */
    public function scopeActive($query)
    {
        return $this->scopeConnected($query);
    }

    /**
     * Sender yang ditandai sebagai default.
     */
    public function scopeDefault($query)
    {
        $table = $this->getTable();

        if (Schema::hasColumn($table, 'is_default')) {
            return $query->where('is_default', 1);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Pencarian ringan di beberapa kolom umum.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        $term = trim($term);
        $table = $this->getTable();

        return $query->where(function ($q) use ($term, $table) {
            if (Schema::hasColumn($table, 'name')) {
                $q->orWhere('name', 'like', "%{$term}%");
            }
            if (Schema::hasColumn($table, 'display_name')) {
                $q->orWhere('display_name', 'like', "%{$term}%");
            }
            if (Schema::hasColumn($table, 'number')) {
                $q->orWhere('number', 'like', "%{$term}%");
            }
            if (Schema::hasColumn($table, 'session_name')) {
                $q->orWhere('session_name', 'like', "%{$term}%");
            }
        });
    }

    /* -----------------------------------------------------------------
     |  Accessors (read-only, tidak mengubah arsitektur/DB)
     |------------------------------------------------------------------ */

    /**
     * Kunci sesi yang dinormalisasi (lowercase & strip spasi).
     * Tidak dipakai untuk query DB; hanya utilitas tampilan/log.
     */
    public function canonicalSessionKey(): Attribute
    {
        return Attribute::get(function () {
            $key = $this->session_name ?: $this->session;
            if (!$key) {
                return null;
            }
            return strtolower(preg_replace('/\s+/', '-', trim($key)));
        });
    }
}
