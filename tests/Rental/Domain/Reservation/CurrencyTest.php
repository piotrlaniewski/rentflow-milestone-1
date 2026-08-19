<?php

declare(strict_types=1);

namespace App\Tests\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Currency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public function test_creates_three_letter_uppercase_currency_code(): void
    {
        self::assertSame('EUR', Currency::fromCode('EUR')->code());
    }

    #[DataProvider('invalidCurrencyCodes')]
    public function test_rejects_invalid_currency_code_format(string $code): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Currency::fromCode($code);
    }

    public static function invalidCurrencyCodes(): iterable
    {
        yield 'too short' => ['PL'];
        yield 'too long' => ['EURO'];
        yield 'lowercase' => ['pln'];
        yield 'digits' => ['123'];
        yield 'special characters' => ['PŁN'];
    }
}
