<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScreeningPackage extends Model
{
    public const PURPOSES = [
        'pre_employment' => 'Pre-employment medical',
        'annual_medical' => 'Annual medical',
        'occupational_health' => 'Occupational health',
        'risk_screening' => 'Disease-risk screening',
    ];

    protected $fillable = ['code', 'name', 'description', 'purpose', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(DiagnosticTest::class, 'screening_package_tests');
    }
}
