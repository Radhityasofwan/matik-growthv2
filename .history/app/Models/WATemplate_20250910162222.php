<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WATemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'body', 'variables'];

    protected $casts = [
        'variables' => 'array',
    ];

    // --- FIX: Secara eksplisit mendefinisikan nama tabel yang benar ---
    protected $table = 'wa_templates';
}

