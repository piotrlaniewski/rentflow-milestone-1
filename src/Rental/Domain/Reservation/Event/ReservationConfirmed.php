<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Event;

use App\Rental\Domain\Reservation\ReservationId;

final readonly class ReservationConfirmed implements DomainEvent
{
    public function __construct(
        public ReservationId $reservationId,
        private \DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
