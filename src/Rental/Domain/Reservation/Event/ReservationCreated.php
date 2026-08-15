<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Event;

use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Shared\Domain\CustomerId;
use App\Shared\Domain\VehicleId;

final readonly class ReservationCreated implements DomainEvent
{
    public function __construct(
        public ReservationId $reservationId,
        public CustomerId $customerId,
        public VehicleId $vehicleId,
        public ReservationPeriod $period,
        private \DateTimeImmutable $occurredAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
