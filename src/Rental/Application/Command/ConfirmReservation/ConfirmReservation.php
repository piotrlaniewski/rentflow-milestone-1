<?php

declare(strict_types=1);

namespace App\Rental\Application\Command\ConfirmReservation;

final readonly class ConfirmReservation
{
    public function __construct(public string $reservationId) {}
}
