<?php

declare(strict_types=1);

namespace App\Tests\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Event\ReservationCancelled;
use App\Rental\Domain\Reservation\Event\ReservationConfirmed;
use App\Rental\Domain\Reservation\Event\ReservationCreated;
use App\Rental\Domain\Reservation\Exception\ReservationCannotBeConfirmed;
use App\Rental\Domain\Reservation\ReservationStatus;
use App\Tests\Rental\Support\ReservationMother;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    private const OCCURRED_AT = '2026-08-19 10:15:00';

    public function test_creates_pending_reservation(): void
    {
        self::assertSame(ReservationStatus::Pending, ReservationMother::pending()->status());
    }

    public function test_creation_records_domain_event_with_given_time(): void
    {
        $occurredAt = new \DateTimeImmutable(self::OCCURRED_AT);
        $reservation = ReservationMother::pending(occurredAt: $occurredAt);
        $events = $reservation->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCreated::class, $events[0]);
        self::assertEquals($occurredAt, $events[0]->occurredAt());
    }

    public function test_pending_reservation_can_be_confirmed(): void
    {
        $reservation = ReservationMother::pending();
        $reservation->confirm(new \DateTimeImmutable(self::OCCURRED_AT));
        self::assertSame(ReservationStatus::Confirmed, $reservation->status());
    }

    public function test_confirmation_records_domain_event_with_given_time(): void
    {
        $reservation = ReservationMother::pending();
        $reservation->releaseDomainEvents();
        $occurredAt = new \DateTimeImmutable(self::OCCURRED_AT);
        $reservation->confirm($occurredAt);
        $events = $reservation->releaseDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationConfirmed::class, $events[0]);
        self::assertEquals($occurredAt, $events[0]->occurredAt());
    }

    public function test_confirmed_reservation_cannot_be_confirmed_again(): void
    {
        $reservation = ReservationMother::pending();
        $reservation->confirm(new \DateTimeImmutable(self::OCCURRED_AT));
        $this->expectException(ReservationCannotBeConfirmed::class);
        $reservation->confirm(new \DateTimeImmutable(self::OCCURRED_AT));
    }

    public function test_cancelled_reservation_cannot_be_confirmed(): void
    {
        $reservation = ReservationMother::pending();
        $reservation->cancel(new \DateTimeImmutable(self::OCCURRED_AT));
        $this->expectException(ReservationCannotBeConfirmed::class);
        $reservation->confirm(new \DateTimeImmutable(self::OCCURRED_AT));
    }

    public function test_cancellation_records_domain_event(): void
    {
        $reservation = ReservationMother::pending();
        $reservation->releaseDomainEvents();
        $occurredAt = new \DateTimeImmutable(self::OCCURRED_AT);
        $reservation->cancel($occurredAt);
        $events = $reservation->releaseDomainEvents();
        self::assertSame(ReservationStatus::Cancelled, $reservation->status());
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCancelled::class, $events[0]);
        self::assertEquals($occurredAt, $events[0]->occurredAt());
    }
}
