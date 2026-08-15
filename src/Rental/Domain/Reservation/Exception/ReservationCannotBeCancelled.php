<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Exception;

use App\Rental\Domain\Reservation\ReservationStatus;

final class ReservationCannotBeCancelled extends \DomainException
{
    public static function becauseOfStatus(
        ReservationStatus $status,
    ): self {
        return new self(
            sprintf(
                'Reservation cannot be cancelled because its current status is "%s".',
                $status->value,
            )
        );
    }
}
