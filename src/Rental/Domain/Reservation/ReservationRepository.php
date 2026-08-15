<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function get(ReservationId $id): Reservation;
}
