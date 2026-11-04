<?php

declare(strict_types=1);

namespace App\Domain\Support;

use App\Models\IdempotencyRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Prevents duplicate side effects from repeated submissions (double clicks, retries, webhook redelivery).
 */
final class Idempotency
{
    /**
     * Returns the previously created resource for (scope, key) if any; otherwise runs $callback and records the
     * resulting model. Must be called inside the caller's DB transaction.
     *
     * @template T of Model
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function once(string $scope, ?string $key, ?int $userId, string $modelClass, callable $callback): Model
    {
        if ($key === null || $key === '') {
            return $callback();
        }

        $existing = IdempotencyRecord::query()->where('scope', $scope)->where('key', $key)->first();
        if ($existing && $existing->resource_id) {
            return $modelClass::query()->findOrFail($existing->resource_id);
        }

        $model = $callback();
        try {
            IdempotencyRecord::query()->create([
                'scope' => $scope, 'key' => $key, 'user_id' => $userId,
                'resource_type' => class_basename($model), 'resource_id' => $model->getKey(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new \RuntimeException('A concurrent identical request is being processed.');
        }

        return $model;
    }
}
