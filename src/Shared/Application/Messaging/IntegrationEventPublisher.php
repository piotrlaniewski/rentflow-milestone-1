<?php

declare(strict_types=1);

namespace App\Shared\Application\Messaging;

interface IntegrationEventPublisher
{
    public function publish(OutboxMessage $message): void;
}
