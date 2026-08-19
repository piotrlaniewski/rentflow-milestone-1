<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Domain\Clock\Clock;

final readonly class FrozenClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now) {}
    public function now(): \DateTimeImmutable { return $this->now; }
}
