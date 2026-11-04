<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticSpecialty extends Model
{
    protected $fillable = ['code', 'name', 'required_qualification_type'];

    public function tests(): HasMany
    {
        return $this->hasMany(DiagnosticTest::class);
    }
}
