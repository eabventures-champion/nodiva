<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionSession extends Model
{
    use HasPublicUuid;

    protected $fillable = ['screening_program_id', 'organization_branch_id', 'location', 'mode', 'starts_at', 'ends_at', 'capacity'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(ScreeningProgram::class, 'screening_program_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ScreeningAppointment::class);
    }

    public function remaining(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }
}
