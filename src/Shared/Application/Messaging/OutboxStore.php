<?php

declare(strict_types=1);

namespace App\Shared\Application\Messaging;

interface OutboxStore
{
    /** @return list<OutboxMessage> */
    public function claimBatch(int $limit, int $maxAttempts): array;

    public function markPublished(OutboxMessage $message): void;

    public function releaseForRetry(OutboxMessage $message, string $reason): void;

    public function markFailed(OutboxMessage $message, string $reason): void;
}
