<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Exception\InvalidReservationPeriod;

final readonly class ReservationPeriod
{
    public function __construct(
        private \DateTimeImmutable $from,
        private \DateTimeImmutable $to,
    ) {
        if ($from >= $to) {
            throw InvalidReservationPeriod::endMustBeAfterStart();
        }
    }

    public function from(): \DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): \DateTimeImmutable
    {
        return $this->to;
    }

    public function overlaps(self $other): bool
    {
        return $this->from < $other->to
            && $other->from < $this->to;
    }

    public function extends(self $current): bool
    {
        return $this->from == $current->from
            && $this->to > $current->to;
    }

    public function numberOfDays(): int
    {
        return (int) $this->from->diff($this->to)->days;
    }

    public function equals(self $other): bool
    {
        return $this->from == $other->from
            && $this->to == $other->to;
    }
}
