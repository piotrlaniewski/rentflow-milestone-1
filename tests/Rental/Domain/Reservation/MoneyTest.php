<?php

declare(strict_types=1);

namespace App\Tests\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Currency;
use App\Rental\Domain\Reservation\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_creates_money_with_currency_value_object(): void
    {
        $money = new Money(12_345, Currency::fromCode('EUR'));
        self::assertSame(12_345, $money->amount());
        self::assertSame('EUR', $money->currency()->code());
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1, Currency::pln());
    }
}
