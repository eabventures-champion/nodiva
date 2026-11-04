<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasPublicUuid;

    protected $fillable = ['reference', 'title', 'contract_type', 'starts_on', 'ends_on', 'terms', 'created_by'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'terms' => 'array'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
