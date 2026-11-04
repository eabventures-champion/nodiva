<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationBranch extends Model
{
    protected $fillable = ['organization_id', 'name', 'code', 'address_line', 'city', 'service_region_id', 'phone', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(ServiceRegion::class, 'service_region_id');
    }
}
