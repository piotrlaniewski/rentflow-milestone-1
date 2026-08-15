<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

use App\Shared\Domain\VehicleId;

interface ReservationPricing
{
    public function calculate(
        VehicleId $vehicleId,
        ReservationPeriod $period,
    ): Money;
}
