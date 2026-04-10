<?php

namespace Tests\Unit;

use App\ValueObjects\Money;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_parses_decimal_to_cents(): void
    {
        $money = Money::fromDecimal('123.45');

        $this->assertSame(12345, $money->cents());
    }

    public function test_it_formats_cents_to_decimal(): void
    {
        $money = Money::fromCents(2599);

        $this->assertSame('25.99', $money->toDecimal());
    }

    public function test_it_supports_arithmetic_without_float_precision_loss(): void
    {
        $first = Money::fromDecimal('0.10');
        $second = Money::fromDecimal('0.20');

        $result = $first->add($second);

        $this->assertSame(30, $result->cents());
        $this->assertSame('0.30', $result->toDecimal());
    }

    public function test_it_rejects_more_than_two_decimal_places(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('1.999');
    }
}
