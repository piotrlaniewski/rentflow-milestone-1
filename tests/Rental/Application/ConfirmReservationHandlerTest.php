<?php

declare(strict_types=1);

namespace App\Tests\Rental\Application;

use App\Rental\Application\Command\ConfirmReservation\ConfirmReservation;
use App\Rental\Application\Command\ConfirmReservation\ConfirmReservationHandler;
use App\Rental\Domain\Reservation\Event\ReservationConfirmed;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationStatus;
use App\Tests\Rental\Support\InMemoryReservationRepository;
use App\Tests\Rental\Support\ReservationMother;
use App\Tests\Shared\FrozenClock;
use PHPUnit\Framework\TestCase;

final class ConfirmReservationHandlerTest extends TestCase
{
    public function test_confirms_pending_reservation(): void
    {
        $repository = new InMemoryReservationRepository();
        $id = ReservationId::fromString('reservation-1');
        $reservation = ReservationMother::pending($id);
        $reservation->releaseDomainEvents();
        $repository->save($reservation);
        $now = new \DateTimeImmutable('2026-08-19 11:00:00');

        $handler = new ConfirmReservationHandler($repository, new FrozenClock($now));
        $handler(new ConfirmReservation($id->toString()));

        $saved = $repository->get($id);
        $events = $saved->releaseDomainEvents();
        self::assertSame(ReservationStatus::Confirmed, $saved->status());
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationConfirmed::class, $events[0]);
        self::assertEquals($now, $events[0]->occurredAt());
    }
}
