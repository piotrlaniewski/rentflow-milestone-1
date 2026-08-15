<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Exception;

use App\Rental\Domain\Reservation\ReservationStatus;

final class ReservationCannotBeConfirmed extends \DomainException
{
    public static function becauseOfStatus(
        ReservationStatus $status,
    ): self {
        return new self(
            sprintf(
                'Reservation cannot be confirmed because its current status is "%s".',
                $status->value,
            )
        );
    }
}
