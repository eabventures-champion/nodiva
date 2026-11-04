<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['key', 'name', 'organization_type', 'description', 'is_admin'];

    protected function casts(): array
    {
        return ['is_admin' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasPermission(string $key): bool
    {
        return $this->permissions->contains('key', $key);
    }
}
