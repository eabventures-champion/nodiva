<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    protected $fillable = ['name', 'organization_branch_id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
