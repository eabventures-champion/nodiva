<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralOrderItem extends Model
{
    protected $fillable = ['referral_order_id', 'diagnostic_test_id', 'test_catalogue_entry_id', 'quoted_price', 'agreed_price', 'turnaround_hours'];

    protected function casts(): array
    {
        return ['quoted_price' => 'decimal:2', 'agreed_price' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(DiagnosticTest::class, 'diagnostic_test_id');
    }

    public function catalogueEntry(): BelongsTo
    {
        return $this->belongsTo(TestCatalogueEntry::class, 'test_catalogue_entry_id');
    }
}
