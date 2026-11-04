<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Support\ReferenceGenerator;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScreeningProgram extends Model
{
    use HasPublicUuid;

    protected $fillable = ['reference', 'organization_id', 'screening_package_id', 'laboratory_id', 'name', 'purpose', 'status', 'starts_on', 'ends_on', 'budget', 'currency', 'pricing_model', 'unit_price', 'eligibility_notes'];

    protected static function booted(): void
    {
        static::creating(function (ScreeningProgram $program) {
            if (empty($program->reference)) {
                $program->reference = ReferenceGenerator::make('PRG', 'screening_programs', 'reference');
            }
        });
    }

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'budget' => 'decimal:2', 'unit_price' => 'decimal:2'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ScreeningPackage::class, 'screening_package_id');
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'laboratory_id');
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(DiagnosticTest::class, 'screening_program_tests');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProgramParticipant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CollectionSession::class)->orderBy('starts_at');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(EmployeeImport::class)->latest('id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
