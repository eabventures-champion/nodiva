<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestCatalogueEntry extends Model
{
    protected $fillable = ['organization_id', 'diagnostic_test_id', 'lab_test_code', 'price', 'currency', 'turnaround_hours', 'daily_capacity', 'is_available', 'notes'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_available' => 'boolean'];
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(DiagnosticTest::class, 'diagnostic_test_id');
    }
}
