<?php

declare(strict_types=1);

namespace App\Rental\Application\Command\CancelReservation;

use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationRepository;
use App\Shared\Domain\Clock\Clock;

final readonly class CancelReservationHandler
{
    public function __construct(
        private ReservationRepository $reservations,
        private Clock $clock,
    ) {
    }

    public function __invoke(CancelReservation $command): void
    {
        $reservation = $this->reservations->get(ReservationId::fromString($command->reservationId));
        $reservation->cancel($this->clock->now());
        $this->reservations->save($reservation);
    }
}
