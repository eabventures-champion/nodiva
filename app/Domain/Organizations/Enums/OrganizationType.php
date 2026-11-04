<?php

declare(strict_types=1);

namespace App\Domain\Organizations\Enums;

enum OrganizationType: string
{
    case Hospital = 'hospital';
    case Laboratory = 'laboratory';
    case Corporate = 'corporate';
    case Platform = 'platform';

    public function label(): string
    {
        return match ($this) {
            self::Platform => 'NODIVA Operations',
            default => ucfirst($this->value),
        };
    }

    /** Route-name prefix of the workspace serving this organisation type. */
    public function workspace(): string
    {
        return match ($this) {
            self::Hospital => 'hospital',
            self::Laboratory => 'lab',
            self::Corporate => 'corporate',
            self::Platform => 'ops',
        };
    }

    /** @return list<self> */
    public static function registrable(): array
    {
        return [self::Hospital, self::Laboratory, self::Corporate];
    }
}
