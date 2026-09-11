<?php

declare(strict_types=1);

namespace Nexum\Messenger;

use Symfony\Component\Messenger\MessageBusInterface as SymfonyBus;

final readonly class SymfonyMessageBus implements MessageBusInterface
{
    public function __construct(private SymfonyBus $bus)
    {
    }

    public function dispatch(object $message): object
    {
        return $this->bus->dispatch($message);
    }
}
