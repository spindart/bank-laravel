<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Money
{
    public function __construct(private readonly int $cents)
    {
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromDecimal(string|int|float $value): self
    {
        $normalized = str_replace(',', '.', trim((string) $value));

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Invalid money amount format.');
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        [$integerPart, $fractionalPart] = array_pad(explode('.', $unsigned, 2), 2, '0');
        $fractionalPart = str_pad($fractionalPart, 2, '0');

        $cents = ((int) $integerPart * 100) + (int) substr($fractionalPart, 0, 2);

        return new self($negative ? -$cents : $cents);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function toDecimal(): string
    {
        $absolute = abs($this->cents);
        $integerPart = intdiv($absolute, 100);
        $fractionalPart = $absolute % 100;
        $prefix = $this->cents < 0 ? '-' : '';

        return sprintf('%s%d.%02d', $prefix, $integerPart, $fractionalPart);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function lessThan(self $other): bool
    {
        return $this->cents < $other->cents;
    }
}
