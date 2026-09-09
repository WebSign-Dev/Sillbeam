<?php

declare(strict_types=1);

namespace Nexum\Messenger;

interface MessageBusInterface
{
    public function dispatch(object $message): object;
}
