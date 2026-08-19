<?php

declare(strict_types=1);

namespace App\Tests\Rental\Support;

use App\Rental\Domain\Reservation\Reservation;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationRepository;

final class InMemoryReservationRepository implements ReservationRepository
{
    /** @var array<string, Reservation> */
    public array $reservations = [];

    public function save(Reservation $reservation): void
    {
        $this->reservations[$reservation->id()->toString()] = $reservation;
    }

    public function get(ReservationId $id): Reservation
    {
        $key = $id->toString();
        if (!isset($this->reservations[$key])) {
            throw new \RuntimeException(sprintf('Reservation "%s" was not found.', $key));
        }
        return $this->reservations[$key];
    }
}
