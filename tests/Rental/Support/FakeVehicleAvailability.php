<?php

declare(strict_types=1);

namespace App\Tests\Rental\Support;

use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\VehicleAvailability;
use App\Shared\Domain\VehicleId;

final readonly class FakeVehicleAvailability implements VehicleAvailability
{
    public function __construct(private bool $available) {}
    public function isAvailable(VehicleId $vehicleId, ReservationPeriod $period): bool
    {
        return $this->available;
    }
}
