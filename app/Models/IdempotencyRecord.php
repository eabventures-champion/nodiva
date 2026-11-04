<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyRecord extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['key', 'scope', 'user_id', 'resource_type', 'resource_id'];
}
