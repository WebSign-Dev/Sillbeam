<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

final readonly class ArchitectureFinding
{
    /** @param array<string, string|int|null> $context
     * @param 'error'|'fatal' $severity
     */
    public function __construct(
        public string $rule,
        public string $message,
        public array $context = [],
        public string $severity = 'error',
    ) {
    }
}
