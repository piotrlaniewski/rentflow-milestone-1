<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Exception;

final class InvalidReservationPeriod extends \DomainException
{
    public static function endMustBeAfterStart(): self
    {
        return new self(
            'Reservation end date must be later than start date.'
        );
    }
}
