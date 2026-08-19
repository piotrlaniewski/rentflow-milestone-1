<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

final readonly class Currency
{
    private function __construct(
        private string $code,
    ) {
        if (preg_match('/^[A-Z]{3}$/D', $code) !== 1) {
            throw new \InvalidArgumentException(
                'Currency code must contain exactly three uppercase ASCII letters.'
            );
        }
    }

    public static function fromCode(string $code): self
    {
        return new self($code);
    }

    public static function pln(): self
    {
        return new self('PLN');
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
