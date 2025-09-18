<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'trigger_config',
        'action_type',
        'action_config',
        'is_active',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'action_config' => 'array',
        'is_active' => 'boolean',
    ];
}
