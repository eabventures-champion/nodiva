<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaboratorySettlement extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'paid_at' => 'datetime', 'gross_service_value' => 'decimal:2', 'platform_fees' => 'decimal:2', 'net_payable' => 'decimal:2'];
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'laboratory_id');
    }

    public function financials(): HasMany
    {
        return $this->hasMany(OrderFinancial::class);
    }
}
