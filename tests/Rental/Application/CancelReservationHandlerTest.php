<?php

declare(strict_types=1);

namespace App\Tests\Rental\Application;

use App\Rental\Application\Command\CancelReservation\CancelReservation;
use App\Rental\Application\Command\CancelReservation\CancelReservationHandler;
use App\Rental\Domain\Reservation\Event\ReservationCancelled;
use App\Rental\Domain\Reservation\ReservationId;
use App\Rental\Domain\Reservation\ReservationStatus;
use App\Tests\Rental\Support\InMemoryReservationRepository;
use App\Tests\Rental\Support\ReservationMother;
use App\Tests\Shared\FrozenClock;
use PHPUnit\Framework\TestCase;

final class CancelReservationHandlerTest extends TestCase
{
    public function test_cancels_pending_reservation(): void
    {
        $repository = new InMemoryReservationRepository();
        $id = ReservationId::fromString('reservation-1');
        $reservation = ReservationMother::pending($id);
        $reservation->releaseDomainEvents();
        $repository->save($reservation);
        $now = new \DateTimeImmutable('2026-08-19 11:30:00');

        $handler = new CancelReservationHandler($repository, new FrozenClock($now));
        $handler(new CancelReservation($id->toString()));

        $saved = $repository->get($id);
        $events = $saved->releaseDomainEvents();
        self::assertSame(ReservationStatus::Cancelled, $saved->status());
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCancelled::class, $events[0]);
        self::assertEquals($now, $events[0]->occurredAt());
    }
}
