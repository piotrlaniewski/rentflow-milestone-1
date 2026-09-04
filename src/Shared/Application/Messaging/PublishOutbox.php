<?php

declare(strict_types=1);

namespace App\Shared\Application\Messaging;

final readonly class PublishOutbox
{
    public function __construct(
        private OutboxStore $outbox,
        private IntegrationEventPublisher $publisher,
    ) {
    }

    public function publishBatch(int $limit, int $maxAttempts): PublishReport
    {
        $published = 0;
        $scheduledForRetry = 0;
        $failedPermanently = 0;

        foreach ($this->outbox->claimBatch($limit, $maxAttempts) as $message) {
            try {
                $this->publisher->publish($message);
                $this->outbox->markPublished($message);
                ++$published;
            } catch (\Throwable $exception) {
                if ($message->attempt >= $maxAttempts) {
                    $this->outbox->markFailed($message, $exception->getMessage());
                    ++$failedPermanently;
                } else {
                    $this->outbox->releaseForRetry($message, $exception->getMessage());
                    ++$scheduledForRetry;
                }
            }
        }

        return new PublishReport($published, $scheduledForRetry, $failedPermanently);
    }
}
