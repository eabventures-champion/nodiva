<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationVerification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['laboratory_qualification_id', 'action', 'notes', 'actor_id'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
