<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriticalResultAcknowledgement extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'organization_id', 'notes', 'acknowledged_at'];

    protected function casts(): array
    {
        return ['acknowledged_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
