<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WATemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'body', 'variables', 'is_active'];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected $table = 'wa_templates';

    /** Return normalized variable names (without {{ }}) for legacy rows too */
    public function normalizedVariables(): array
    {
        $vars = $this->variables ?? [];
        if (!is_array($vars)) $vars = [];
        $norm = array_map(function ($v) {
            $s = (string) $v;
            // remove {{   }} if stored with braces
            $s = preg_replace('/^\s*\{\{\s*|\s*\}\}\s*$/', '', $s);
            return $s;
        }, $vars);
        // unique & reindex
        return array_values(array_unique(array_filter($norm, fn($x) => $x !== '')));
    }

    /* Scopes opsional */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('body', 'like', "%{$term}%")
              ->orWhereJsonContains('variables', $term);
        });
    }

    public function scopeActive($query)   { return $query->where('is_active', true);  }
    public function scopeInactive($query) { return $query->where('is_active', false); }
}
