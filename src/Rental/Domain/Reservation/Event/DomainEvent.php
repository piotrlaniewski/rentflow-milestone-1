<?php

declare(strict_types=1);

namespace App\Rental\Domain\Reservation\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
