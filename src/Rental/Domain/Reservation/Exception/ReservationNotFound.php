<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Exception;

final class ReservationNotFound extends \RuntimeException
{
    public static function withId(string $reservationId): self
    {
        return new self(sprintf('Reservation "%s" was not found.', $reservationId));
    }
}

