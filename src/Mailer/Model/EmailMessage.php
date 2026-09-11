<?php

declare(strict_types=1);

namespace Nexum\Mailer\Model;

final readonly class EmailMessage
{
    /** @param list<string> $to */
    public function __construct(
        public array $to,
        public string $subject,
        public ?string $text = null,
        public ?string $html = null,
        public ?string $from = null,
    ) {
        if ([] === $to || array_any($to, static fn (string $address): bool => false === filter_var($address, \FILTER_VALIDATE_EMAIL))) {
            throw new \InvalidArgumentException('At least one valid recipient is required.');
        }
        if ('' === trim($subject) || str_contains($subject, "\r") || str_contains($subject, "\n")) {
            throw new \InvalidArgumentException('The email subject must be non-empty and contain no line breaks.');
        }
        if (null === $text && null === $html) {
            throw new \InvalidArgumentException('A text or HTML body is required.');
        }
        if (null !== $from && false === filter_var($from, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The sender address is invalid.');
        }
    }
}
