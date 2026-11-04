<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Specimens\Enums\SpecimenStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specimen extends Model
{
    use HasPublicUuid;

    protected $fillable = ['referral_order_id', 'identifier', 'specimen_type_id', 'status', 'sequence', 'custodian_organization_id'];

    protected function casts(): array
    {
        return ['status' => SpecimenStatus::class, 'collected_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ReferralOrder::class, 'referral_order_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(SpecimenType::class, 'specimen_type_id');
    }

    public function custodyEvents(): HasMany
    {
        return $this->hasMany(ChainOfCustodyEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'custodian_organization_id');
    }
}
