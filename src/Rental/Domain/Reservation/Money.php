<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

final readonly class Money
{
    public function __construct(
        private int $amount,
        private string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException(
                'Money amount cannot be negative.'
            );
        }

        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException(
                'Currency must use ISO 4217 format.'
            );
        }
    }

    public static function pln(int $amount): self
    {
        return new self($amount, 'PLN');
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }
}
