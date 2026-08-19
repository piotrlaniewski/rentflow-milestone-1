<?php

declare(strict_types=1);

namespace App\Tests\Rental\Support;

use App\Rental\Domain\Reservation\Money;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\ReservationPricing;
use App\Shared\Domain\VehicleId;

final readonly class FakeReservationPricing implements ReservationPricing
{
    public function __construct(private Money $price) {}
    public function calculate(VehicleId $vehicleId, ReservationPeriod $period): Money
    {
        return $this->price;
    }
}
