<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

final readonly class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException(
                'Money amount cannot be negative.'
            );
        }
    }

    public static function pln(int $amount): self
    {
        return new self($amount, Currency::pln());
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency->equals($other->currency);
    }
}
