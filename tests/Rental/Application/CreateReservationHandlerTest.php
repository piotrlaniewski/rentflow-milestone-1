<?php

declare(strict_types=1);

namespace App\Tests\Rental\Application;

use App\Rental\Application\Command\CreateReservation\CreateReservation;
use App\Rental\Application\Command\CreateReservation\CreateReservationHandler;
use App\Rental\Application\Exception\VehicleNotAvailable;
use App\Rental\Domain\Reservation\Money;
use App\Rental\Domain\Reservation\Reservation;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\ReservationPricing;
use App\Rental\Domain\Reservation\ReservationRepository;
use App\Rental\Domain\Reservation\VehicleAvailability;
use App\Shared\Domain\VehicleId;
use PHPUnit\Framework\TestCase;

final class CreateReservationHandlerTest extends TestCase
{
    public function test_creates_reservation(): void
    {
        $repository = new InMemoryReservationRepository();
        $availability = new FakeVehicleAvailability(true);
        $pricing = new FakeReservationPricing(Money::pln(120_000));

        $handler = new CreateReservationHandler(
            $repository,
            $availability,
            $pricing,
        );

        $handler(
            new CreateReservation(
                customerId: 'customer-1',
                vehicleId: 'vehicle-1',
                from: new \DateTimeImmutable('2026-09-01'),
                to: new \DateTimeImmutable('2026-09-05'),
            )
        );

        self::assertCount(
            1,
            $repository->reservations
        );
    }

    public function test_cannot_reserve_unavailable_vehicle(): void
    {
        $handler = new CreateReservationHandler(
            new InMemoryReservationRepository(),
            new FakeVehicleAvailability(false),
            new FakeReservationPricing(
                Money::pln(120_000)
            ),
        );

        $this->expectException(
            VehicleNotAvailable::class
        );

        $handler(
            new CreateReservation(
                customerId: 'customer-1',
                vehicleId: 'vehicle-1',
                from: new \DateTimeImmutable('2026-09-01'),
                to: new \DateTimeImmutable('2026-09-05'),
            )
        );
    }
}

final class InMemoryReservationRepository implements ReservationRepository
{
    /**
     * @var array<string, Reservation>
     */
    public array $reservations = [];

    public function save(Reservation $reservation): void
    {
        $this->reservations[
            $reservation->id()->toString()
        ] = $reservation;
    }

    public function get(ReservationId $id): Reservation
    {
        return $this->reservations[
            $id->toString()
        ];
    }
}

final readonly class FakeVehicleAvailability implements VehicleAvailability
{
    public function __construct(
        private bool $available,
    ) {
    }

    public function isAvailable(
        VehicleId $vehicleId,
        ReservationPeriod $period,
    ): bool {
        return $this->available;
    }
}

final readonly class FakeReservationPricing implements ReservationPricing
{
    public function __construct(
        private Money $price,
    ) {
    }

    public function calculate(
        VehicleId $vehicleId,
        ReservationPeriod $period,
    ): Money {
        return $this->price;
    }
}
