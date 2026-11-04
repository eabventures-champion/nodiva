<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Adds a non-guessable public UUID used for routing. UUIDs never replace authorisation checks.
 */
trait HasPublicUuid
{
    public static function bootHasPublicUuid(): void
    {
        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        if (is_numeric($value)) {
            return $this->where($field, $value)->orWhere($this->getKeyName(), (int) $value)->first();
        }

        return $this->where($field, $value)->first();
    }
}
