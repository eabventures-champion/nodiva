<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use InvalidArgumentException;

/**
 * Immutable decimal-safe money value using bcmath string arithmetic.
 * Amounts are stored with 2 decimal places (DECIMAL(15,2)) and an explicit ISO-4217 currency.
 */
final class Money implements \JsonSerializable, \Stringable
{
    private const SCALE = 2;

    private function __construct(public readonly string $amount, public readonly string $currency)
    {
    }

    public static function of(string|int $amount, string $currency): self
    {
        $amount = (string) $amount;
        if (! preg_match('/^-?\d+(\.\d+)?$/', $amount)) {
            throw new InvalidArgumentException("Invalid monetary amount [{$amount}].");
        }
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException("Invalid currency [{$currency}].");
        }

        return new self(self::round($amount), $currency);
    }

    public static function zero(string $currency): self
    {
        return self::of('0', $currency);
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcadd($this->amount, $other->amount, self::SCALE), $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcsub($this->amount, $other->amount, self::SCALE), $this->currency);
    }

    public function multiply(string|int $factor): self
    {
        return new self(self::round(bcmul($this->amount, (string) $factor, 8)), $this->currency);
    }

    /** Percentage of this amount, e.g. percentage('12.00') => 12%. Half-up rounding to 2 dp. */
    public function percentage(string $rate): self
    {
        return new self(self::round(bcdiv(bcmul($this->amount, $rate, 8), '100', 8)), $this->currency);
    }

    public function compare(self $other): int
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, self::SCALE);
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) < 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) > 0;
    }

    public function min(self $other): self
    {
        return $this->compare($other) <= 0 ? $this : $other;
    }

    public function format(): string
    {
        $negative = str_starts_with($this->amount, '-');
        [$int, $dec] = explode('.', ltrim($this->amount, '-'));

        return ($negative ? '-' : '').$this->currency.' '.number_format((float) $int, 0, '.', ',').'.'.$dec;
    }

    public function jsonSerialize(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    /** Half-up rounding to SCALE using string arithmetic only. */
    private static function round(string $value): string
    {
        $offset = '0.'.str_repeat('0', self::SCALE).'5';
        $rounded = str_starts_with($value, '-')
            ? bcsub($value, $offset, self::SCALE)
            : bcadd($value, $offset, self::SCALE);

        return $rounded === '-0.00' ? '0.00' : $rounded;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}.");
        }
    }
}
