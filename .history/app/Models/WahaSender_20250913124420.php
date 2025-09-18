<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WahaSender extends Model
{
    use HasFactory;

    protected $table = 'waha_senders';

    protected $fillable = [
        'name',
        'description',
        // kedua-duanya dibolehkan agar aman di berbagai skema
        'session',
        'session_name',
        'number',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
