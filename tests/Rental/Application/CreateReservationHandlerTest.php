<?php

declare(strict_types=1);

namespace App\Tests\Rental\Application;

use App\Rental\Application\Command\CreateReservation\CreateReservation;
use App\Rental\Application\Command\CreateReservation\CreateReservationHandler;
use App\Rental\Application\Exception\VehicleNotAvailable;
use App\Rental\Domain\Reservation\Event\ReservationCreated;
use App\Rental\Domain\Reservation\Money;
use App\Tests\Rental\Support\FakeReservationPricing;
use App\Tests\Rental\Support\FakeVehicleAvailability;
use App\Tests\Rental\Support\InMemoryReservationRepository;
use App\Tests\Shared\FrozenClock;
use PHPUnit\Framework\TestCase;

final class CreateReservationHandlerTest extends TestCase
{
    public function test_creates_reservation_using_current_clock_time(): void
    {
        $repository = new InMemoryReservationRepository();
        $now = new \DateTimeImmutable('2026-08-19 10:30:00');
        $handler = new CreateReservationHandler(
            $repository,
            new FakeVehicleAvailability(true),
            new FakeReservationPricing(Money::pln(120_000)),
            new FrozenClock($now),
        );

        $handler(new CreateReservation(
            reservationId: 'reservation-1',
            customerId: 'customer-1',
            vehicleId: 'vehicle-1',
            from: new \DateTimeImmutable('2026-09-01'),
            to: new \DateTimeImmutable('2026-09-05'),
        ));

        self::assertCount(1, $repository->reservations);
        $reservation = array_values($repository->reservations)[0];
        $events = $reservation->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCreated::class, $events[0]);
        self::assertEquals($now, $events[0]->occurredAt());
    }

    public function test_cannot_reserve_unavailable_vehicle(): void
    {
        $handler = new CreateReservationHandler(
            new InMemoryReservationRepository(),
            new FakeVehicleAvailability(false),
            new FakeReservationPricing(Money::pln(120_000)),
            new FrozenClock(new \DateTimeImmutable('2026-08-19 10:30:00')),
        );

        $this->expectException(VehicleNotAvailable::class);
        $handler(new CreateReservation(
            reservationId: 'reservation-1',
            customerId: 'customer-1',
            vehicleId: 'vehicle-1',
            from: new \DateTimeImmutable('2026-09-01'),
            to: new \DateTimeImmutable('2026-09-05'),
        ));
    }
}
