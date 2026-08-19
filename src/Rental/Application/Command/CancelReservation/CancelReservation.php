<?php

declare(strict_types=1);

namespace App\Rental\Application\Command\CancelReservation;

final readonly class CancelReservation
{
    public function __construct(public string $reservationId) {}
}
