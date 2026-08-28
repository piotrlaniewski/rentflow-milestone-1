<?php

declare(strict_types=1);

namespace App\Rental\Application\Command\CreateReservation;

final readonly class CreateReservation
{
    public function __construct(
        public string $reservationId,
        public string $customerId,
        public string $vehicleId,
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
