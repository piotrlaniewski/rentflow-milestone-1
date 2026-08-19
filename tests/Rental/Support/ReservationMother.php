<?php

declare(strict_types=1);

namespace App\Tests\Rental\Support;

use App\Rental\Domain\Reservation\Money;
use App\Rental\Domain\Reservation\Reservation;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Shared\Domain\CustomerId;
use App\Shared\Domain\VehicleId;

final class ReservationMother
{
    public static function pending(
        ?ReservationId $id = null,
        ?\DateTimeImmutable $occurredAt = null,
    ): Reservation {
        return Reservation::create(
            id: $id ?? ReservationId::fromString('reservation-1'),
            customerId: CustomerId::fromString('customer-1'),
            vehicleId: VehicleId::fromString('vehicle-1'),
            period: new ReservationPeriod(
                new \DateTimeImmutable('2026-09-01'),
                new \DateTimeImmutable('2026-09-05'),
            ),
            price: Money::pln(150_000),
            occurredAt: $occurredAt ?? new \DateTimeImmutable('2026-08-19 10:00:00'),
        );
    }
}
