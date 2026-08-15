<?php

declare(strict_types=1);

namespace App\Tests\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Event\ReservationConfirmed;
use App\Rental\Domain\Reservation\Event\ReservationCreated;
use App\Rental\Domain\Reservation\Exception\ReservationCannotBeConfirmed;
use App\Rental\Domain\Reservation\Money;
use App\Rental\Domain\Reservation\Reservation;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationPeriod;
use App\Rental\Domain\Reservation\ReservationStatus;
use App\Shared\Domain\CustomerId;
use App\Shared\Domain\VehicleId;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    public function test_creates_pending_reservation(): void
    {
        $reservation = $this->createReservation();

        self::assertSame(
            ReservationStatus::Pending,
            $reservation->status()
        );
    }

    public function test_creation_records_domain_event(): void
    {
        $reservation = $this->createReservation();

        $events = $reservation->releaseDomainEvents();

        self::assertCount(1, $events);

        self::assertInstanceOf(
            ReservationCreated::class,
            $events[0]
        );
    }

    public function test_pending_reservation_can_be_confirmed(): void
    {
        $reservation = $this->createReservation();

        $reservation->confirm();

        self::assertSame(
            ReservationStatus::Confirmed,
            $reservation->status()
        );
    }

    public function test_confirmation_records_domain_event(): void
    {
        $reservation = $this->createReservation();

        $reservation->releaseDomainEvents();

        $reservation->confirm();

        $events = $reservation->releaseDomainEvents();

        self::assertCount(1, $events);

        self::assertInstanceOf(
            ReservationConfirmed::class,
            $events[0]
        );
    }

    public function test_confirmed_reservation_cannot_be_confirmed_again(): void
    {
        $reservation = $this->createReservation();

        $reservation->confirm();

        $this->expectException(
            ReservationCannotBeConfirmed::class
        );

        $reservation->confirm();
    }

    public function test_cancelled_reservation_cannot_be_confirmed(): void
    {
        $reservation = $this->createReservation();

        $reservation->cancel();

        $this->expectException(
            ReservationCannotBeConfirmed::class
        );

        $reservation->confirm();
    }

    private function createReservation(): Reservation
    {
        return Reservation::create(
            ReservationId::generate(),
            CustomerId::fromString('customer-1'),
            VehicleId::fromString('vehicle-1'),
            new ReservationPeriod(
                new \DateTimeImmutable('2026-09-01'),
                new \DateTimeImmutable('2026-09-05'),
            ),
            Money::pln(150_000),
        );
    }
}
