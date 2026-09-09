<?php

declare(strict_types=1);

namespace Nexum\Notification;

final class NotificationRouter
{
    /** @var array<string, NotificationChannelInterface> */
    private array $channels = [];

    /** @param iterable<NotificationChannelInterface> $channels */
    public function __construct(iterable $channels)
    {
        foreach ($channels as $channel) {
            if (isset($this->channels[$channel->name()])) {
                throw new \LogicException(\sprintf('Duplicate notification channel "%s".', $channel->name()));
            }
            $this->channels[$channel->name()] = $channel;
        }
    }

    public function send(string $channel, Notification $notification): void
    {
        ($this->channels[$channel] ?? throw new \OutOfBoundsException(\sprintf('Unknown notification channel "%s".', $channel)))->send($notification);
    }
}
