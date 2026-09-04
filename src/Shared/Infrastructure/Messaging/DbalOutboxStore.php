<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Messaging\OutboxMessage;
use App\Shared\Application\Messaging\OutboxStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DbalOutboxStore implements OutboxStore
{
    public function __construct(private Connection $connection) {}

    public function claimBatch(int $limit, int $maxAttempts): array
    {
        if ($limit < 1 || $maxAttempts < 1) {
            throw new \InvalidArgumentException('Outbox limit and max attempts must be positive.');
        }

        $claimId = self::uuidV4();
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                WITH candidates AS (
                    SELECT id
                    FROM outbox_message
                    WHERE published_at IS NULL
                      AND failed_at IS NULL
                      AND attempts < :maxAttempts
                      AND (claimed_at IS NULL OR claimed_at < NOW() - INTERVAL '5 minutes')
                    ORDER BY created_at
                    FOR UPDATE SKIP LOCKED
                    LIMIT :batchSize
                )
                UPDATE outbox_message AS message
                SET claimed_at = NOW(),
                    claim_id = :claimId,
                    attempts = message.attempts + 1
                FROM candidates
                WHERE message.id = candidates.id
                RETURNING message.id, message.aggregate_id, message.event_type,
                          message.payload, message.occurred_at, message.attempts,
                          message.claim_id
            SQL,
            [
                'maxAttempts' => $maxAttempts,
                'batchSize' => $limit,
                'claimId' => $claimId,
            ],
            [
                'maxAttempts' => ParameterType::INTEGER,
                'batchSize' => ParameterType::INTEGER,
                'claimId' => ParameterType::STRING,
            ],
        );

        return array_map(static function (array $row): OutboxMessage {
            $payload = json_decode((string) $row['payload'], true, flags: JSON_THROW_ON_ERROR);

            if (!is_array($payload)) {
                throw new \UnexpectedValueException('Outbox payload must decode to an object.');
            }

            return new OutboxMessage(
                id: (string) $row['id'],
                aggregateId: (string) $row['aggregate_id'],
                eventType: (string) $row['event_type'],
                payload: $payload,
                occurredAt: new \DateTimeImmutable((string) $row['occurred_at']),
                attempt: (int) $row['attempts'],
                claimId: (string) $row['claim_id'],
            );
        }, $rows);
    }

    public function markPublished(OutboxMessage $message): void
    {
        $updatedRows = $this->connection->executeStatement(
            <<<'SQL'
                UPDATE outbox_message
                SET published_at = NOW(), claimed_at = NULL, claim_id = NULL, last_error = NULL
                WHERE id = :id AND claim_id = :claimId AND published_at IS NULL
            SQL,
            ['id' => $message->id, 'claimId' => $message->claimId],
        );

        self::assertClaimOwned($message, $updatedRows);
    }

    public function releaseForRetry(OutboxMessage $message, string $reason): void
    {
        $this->release($message, $reason, false);
    }

    public function markFailed(OutboxMessage $message, string $reason): void
    {
        $this->release($message, $reason, true);
    }

    private function release(OutboxMessage $message, string $reason, bool $permanent): void
    {
        $updatedRows = $this->connection->executeStatement(
            <<<'SQL'
                UPDATE outbox_message
                SET claimed_at = NULL,
                    claim_id = NULL,
                    last_error = :reason,
                    failed_at = CASE WHEN :permanent = 1 THEN NOW() ELSE failed_at END
                WHERE id = :id AND claim_id = :claimId AND published_at IS NULL
            SQL,
            [
                'id' => $message->id,
                'claimId' => $message->claimId,
                'reason' => mb_substr($reason, 0, 2000),
                'permanent' => $permanent ? 1 : 0,
            ],
            ['permanent' => ParameterType::INTEGER],
        );

        self::assertClaimOwned($message, $updatedRows);
    }

    private static function assertClaimOwned(OutboxMessage $message, int $updatedRows): void
    {
        if ($updatedRows !== 1) {
            throw new \RuntimeException(sprintf(
                'Outbox claim for message "%s" is no longer owned by this publisher.',
                $message->id,
            ));
        }
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
