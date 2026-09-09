<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Messaging\IntegrationEventPublisher;
use App\Shared\Application\Messaging\OutboxMessage;

final class KafkaIntegrationEventPublisher implements IntegrationEventPublisher
{
    private ?\RdKafka\Producer $producer = null;
    private ?\RdKafka\ProducerTopic $producerTopic = null;

    public function __construct(
        private readonly string $bootstrapServers,
        private readonly string $topic,
        private readonly int $flushTimeoutMilliseconds,
    ) {
    }

    public function publish(OutboxMessage $message): void
    {
        $producer = $this->producer();
        $this->producerTopic()->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            json_encode([
                'id' => $message->id,
                'type' => $message->eventType,
                'aggregateId' => $message->aggregateId,
                'occurredAt' => $message->occurredAt->format(DATE_ATOM),
                'payload' => $message->payload,
            ], JSON_THROW_ON_ERROR),
            $message->aggregateId,
        );

        $producer->poll(0);

        if ($producer->flush($this->flushTimeoutMilliseconds) !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new \RuntimeException(sprintf('Kafka did not acknowledge outbox message "%s".', $message->id));
        }
    }

    private function producer(): \RdKafka\Producer
    {
        if ($this->producer instanceof \RdKafka\Producer) {
            return $this->producer;
        }

        if (!extension_loaded('rdkafka')) {
            throw new \RuntimeException('The ext-rdkafka extension is required to publish integration events.');
        }

        $configuration = new \RdKafka\Conf();
        $configuration->set('bootstrap.servers', $this->bootstrapServers);
        $configuration->set('client.id', 'rentflow-outbox-publisher');
        $configuration->set('enable.idempotence', 'true');
        $configuration->set('acks', 'all');

        return $this->producer = new \RdKafka\Producer($configuration);
    }

    private function producerTopic(): \RdKafka\ProducerTopic
    {
        return $this->producerTopic ??= $this->producer()->newTopic($this->topic);
    }
}
