<?php

declare(strict_types=1);

namespace App\Rental\Infrastructure\Pricing;

use App\Rental\Domain\Reservation\Money;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\ReservationPricing;
use App\Shared\Domain\VehicleId;

final readonly class DailyRateReservationPricing implements ReservationPricing
{
    public function __construct(private int $dailyRateInMinorUnits) {}

    public function calculate(VehicleId $vehicleId, ReservationPeriod $period): Money
    {
        return Money::pln(max(1, $period->numberOfDays()) * $this->dailyRateInMinorUnits);
    }
}

