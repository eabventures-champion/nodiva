<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Enums;

enum OrganizationStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Suspended, self::Rejected => 'danger',
        };
    }
}
