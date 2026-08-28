<?php

declare(strict_types=1);

namespace App\Rental\Application\Query\GetReservation;

final readonly class GetReservation
{
    public function __construct(public string $reservationId) {}
}

