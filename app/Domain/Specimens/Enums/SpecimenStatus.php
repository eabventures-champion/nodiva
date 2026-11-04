<?php

declare(strict_types=1);

namespace App\Domain\Specimens\Enums;

enum SpecimenStatus: string
{
    case PendingCollection = 'pending_collection';
    case Collected = 'collected';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Disposed = 'disposed';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    public function tone(): string
    {
        return match ($this) {
            self::Accepted => 'success',
            self::Rejected => 'danger',
            self::PendingCollection => 'warning',
            self::Disposed => 'neutral',
            default => 'info',
        };
    }
}
