<?php

declare(strict_types=1);

namespace App\Shared\Application\Messaging;

final readonly class PublishReport
{
    public function __construct(
        public int $published,
        public int $scheduledForRetry,
        public int $failedPermanently,
    ) {
    }

    public function processed(): int
    {
        return $this->published + $this->scheduledForRetry + $this->failedPermanently;
    }
}
