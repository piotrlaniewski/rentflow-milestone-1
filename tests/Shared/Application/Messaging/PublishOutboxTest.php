<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Messaging;

use App\Shared\Application\Messaging\IntegrationEventPublisher;
use App\Shared\Application\Messaging\OutboxMessage;
use App\Shared\Application\Messaging\OutboxStore;
use App\Shared\Application\Messaging\PublishOutbox;
use PHPUnit\Framework\TestCase;

final class PublishOutboxTest extends TestCase
{
    public function test_marks_message_as_published_after_kafka_acknowledgement(): void
    {
        $message = self::message(attempt: 1);
        $store = new RecordingOutboxStore([$message]);
        $publisher = new RecordingIntegrationEventPublisher();

        $report = (new PublishOutbox($store, $publisher))->publishBatch(10, 3);

        self::assertSame([$message], $publisher->published);
        self::assertSame([$message], $store->published);
        self::assertSame(1, $report->published);
        self::assertSame(0, $report->scheduledForRetry);
        self::assertSame(0, $report->failedPermanently);
    }

    public function test_releases_message_for_retry_after_transient_failure(): void
    {
        $message = self::message(attempt: 2);
        $store = new RecordingOutboxStore([$message]);
        $publisher = new RecordingIntegrationEventPublisher(fail: true);

        $report = (new PublishOutbox($store, $publisher))->publishBatch(10, 3);

        self::assertSame([$message], $store->retried);
        self::assertSame([], $store->failed);
        self::assertSame(1, $report->scheduledForRetry);
    }

    public function test_marks_message_as_failed_after_attempt_limit(): void
    {
        $message = self::message(attempt: 3);
        $store = new RecordingOutboxStore([$message]);
        $publisher = new RecordingIntegrationEventPublisher(fail: true);

        $report = (new PublishOutbox($store, $publisher))->publishBatch(10, 3);

        self::assertSame([], $store->retried);
        self::assertSame([$message], $store->failed);
        self::assertSame(1, $report->failedPermanently);
    }

    private static function message(int $attempt): OutboxMessage
    {
        return new OutboxMessage(
            id: 'event-1',
            aggregateId: 'reservation-1',
            eventType: 'rentflow.reservation.created.v1',
            payload: ['reservationId' => 'reservation-1'],
            occurredAt: new \DateTimeImmutable('2026-09-04T12:00:00+00:00'),
            attempt: $attempt,
            claimId: 'claim-1',
        );
    }
}
final class RecordingIntegrationEventPublisher implements IntegrationEventPublisher
{
    /** @var list<OutboxMessage> */
    public array $published = [];

    public function __construct(private readonly bool $fail = false) {}

    public function publish(OutboxMessage $message): void
    {
        if ($this->fail) {
            throw new \RuntimeException('Kafka unavailable.');
        }

        $this->published[] = $message;
    }
}

final class RecordingOutboxStore implements OutboxStore
{
    /** @var list<OutboxMessage> */
    public array $published = [];

    /** @var list<OutboxMessage> */
    public array $retried = [];

    /** @var list<OutboxMessage> */
    public array $failed = [];

    /** @param list<OutboxMessage> $messages */
    public function __construct(private readonly array $messages) {}

    public function claimBatch(int $limit, int $maxAttempts): array
    {
        return array_slice($this->messages, 0, $limit);
    }

    public function markPublished(OutboxMessage $message): void
    {
        $this->published[] = $message;
    }

    public function releaseForRetry(OutboxMessage $message, string $reason): void
    {
        $this->retried[] = $message;
    }

    public function markFailed(OutboxMessage $message, string $reason): void
    {
        $this->failed[] = $message;
    }
}
