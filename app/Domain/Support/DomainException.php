<?php

declare(strict_types=1);

namespace App\Domain\Support;

/** A business-rule violation with a user-safe message. Rendered as a validation-style error. */
class DomainException extends \RuntimeException
{
}
