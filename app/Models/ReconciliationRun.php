<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationRun extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'totals' => 'array'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }
}
