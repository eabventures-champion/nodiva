<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Finance\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-order financial ledger. Separates diagnostic service value (GMV), platform fee, logistics, tax,
 * laboratory payable and platform revenue so that GMV is never reported as revenue.
 */
class OrderFinancial extends Model
{
    protected $guarded = ['id']; // written only by FinancialCalculator; never from request input

    protected function casts(): array
    {
        return [
            'service_value' => 'decimal:2', 'discount_amount' => 'decimal:2', 'logistics_charge' => 'decimal:2',
            'platform_fee_rate' => 'decimal:2', 'platform_fee' => 'decimal:2', 'tax_rate' => 'decimal:2', 'tax_amount' => 'decimal:2',
            'institution_total' => 'decimal:2', 'laboratory_payable' => 'decimal:2', 'platform_revenue' => 'decimal:2',
            'locked_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function feeRule(): BelongsTo
    {
        return $this->belongsTo(PlatformFeeRule::class, 'platform_fee_rule_id');
    }

    public function money(string $field): Money
    {
        return Money::of((string) $this->{$field}, $this->currency);
    }
}
