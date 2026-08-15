<?php

declare(strict_types=1);

namespace App\Tests\Rental\Domain\Reservation;

use App\Rental\Domain\Reservation\Exception\InvalidReservationPeriod;
use App\Rental\Domain\Reservation\ReservationPeriod;
use PHPUnit\Framework\TestCase;

final class ReservationPeriodTest extends TestCase
{
    public function test_creates_valid_period(): void
    {
        $from = new \DateTimeImmutable('2026-09-01 10:00:00');
        $to = new \DateTimeImmutable('2026-09-05 10:00:00');

        $period = new ReservationPeriod($from, $to);

        self::assertEquals($from, $period->from());
        self::assertEquals($to, $period->to());
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $this->expectException(
            InvalidReservationPeriod::class
        );

        new ReservationPeriod(
            new \DateTimeImmutable('2026-09-05'),
            new \DateTimeImmutable('2026-09-01'),
        );
    }

    public function test_periods_overlap(): void
    {
        $first = new ReservationPeriod(
            new \DateTimeImmutable('2026-09-01'),
            new \DateTimeImmutable('2026-09-05'),
        );

        $second = new ReservationPeriod(
            new \DateTimeImmutable('2026-09-04'),
            new \DateTimeImmutable('2026-09-08'),
        );

        self::assertTrue(
            $first->overlaps($second)
        );
    }

    public function test_periods_do_not_overlap(): void
    {
        $first = new ReservationPeriod(
            new \DateTimeImmutable('2026-09-01'),
            new \DateTimeImmutable('2026-09-05'),
        );

        $second = new ReservationPeriod(
            new \DateTimeImmutable('2026-09-05'),
            new \DateTimeImmutable('2026-09-10'),
        );

        self::assertFalse(
            $first->overlaps($second)
        );
    }
}
