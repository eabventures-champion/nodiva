<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRegion extends Model
{
    protected $fillable = ['code', 'name', 'country_code', 'is_launch_active'];

    protected function casts(): array
    {
        return ['is_launch_active' => 'boolean'];
    }
}
