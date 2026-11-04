<?php

declare(strict_types=1);

namespace App\Domain\Results\Enums;

/**
 * Distinguishes data entry, review/authorisation and release. A file upload alone never makes a result "released".
 */
enum ResultStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Released = 'released';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending review',
            default => ucfirst($this->value),
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Released => 'success',
            self::PendingReview => 'warning',
            self::Superseded => 'neutral',
            self::Draft => 'neutral',
        };
    }
}
