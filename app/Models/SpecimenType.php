<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecimenType extends Model
{
    protected $fillable = ['code', 'name', 'container', 'transport_requirements', 'stability_hours'];

    public function tests(): HasMany
    {
        return $this->hasMany(DiagnosticTest::class);
    }
}
