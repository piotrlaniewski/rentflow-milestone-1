<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
