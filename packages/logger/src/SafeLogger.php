<?php

declare(strict_types=1);

namespace Nexum\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class SafeLogger extends AbstractLogger
{
    public function __construct(private readonly LoggerInterface $inner, private readonly ContextSanitizer $sanitizer = new ContextSanitizer())
    {
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->inner->log($level, $message, $this->sanitizer->sanitize($context));
    }
}
