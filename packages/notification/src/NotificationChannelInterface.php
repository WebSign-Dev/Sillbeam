<?php

declare(strict_types=1);

namespace Nexum\Notification;

interface NotificationChannelInterface
{
    public function name(): string;

    public function send(Notification $notification): void;
}
