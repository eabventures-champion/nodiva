<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticTest extends Model
{
    protected $fillable = ['code', 'name', 'diagnostic_specialty_id', 'specimen_type_id', 'loinc_code', 'description', 'patient_preparation', 'requires_clinical_notes', 'is_active'];

    protected function casts(): array
    {
        return ['requires_clinical_notes' => 'boolean', 'is_active' => 'boolean'];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSpecialty::class, 'diagnostic_specialty_id');
    }

    public function specimenType(): BelongsTo
    {
        return $this->belongsTo(SpecimenType::class);
    }

    public function catalogueEntries(): HasMany
    {
        return $this->hasMany(TestCatalogueEntry::class);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like));
    }
}
