<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticResultItem extends Model
{
    public const FLAGS = ['normal', 'low', 'high', 'abnormal', 'critical'];

    protected $fillable = ['diagnostic_result_id', 'referral_order_item_id', 'analyte', 'loinc_code', 'value', 'unit', 'reference_range', 'flag'];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(DiagnosticResult::class, 'diagnostic_result_id');
    }
}
