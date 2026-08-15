<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

use App\Shared\Domain\VehicleId;

interface VehicleAvailability
{
    public function isAvailable(
        VehicleId $vehicleId,
        ReservationPeriod $period,
    ): bool;
}
