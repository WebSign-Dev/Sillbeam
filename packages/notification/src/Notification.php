<?php

declare(strict_types=1);

namespace Nexum\Notification;

final readonly class Notification
{
    /** @param array<string, scalar|null> $data */
    public function __construct(public string $type, public string $recipient, public string $subject, public string $body, public array $data = [])
    {
        if ('' === trim($type) || '' === trim($recipient) || \strlen($body) > 1_048_576) {
            throw new \InvalidArgumentException('Invalid notification.');
        }
    }
}
