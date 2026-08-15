<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

final readonly class ReservationId
{
    private function __construct(
        private string $value,
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('Reservation ID cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self(self::uuidV4());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }
}
