<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CredentialCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'issuing_authority_hint',
        'description',
        'is_compulsory',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_compulsory' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCompulsory(Builder $query): Builder
    {
        return $query->where('is_compulsory', true);
    }
}
