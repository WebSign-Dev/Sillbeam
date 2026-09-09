<?php

declare(strict_types=1);

namespace Nexum\Logger;

final readonly class ContextSanitizer
{
    /** @param list<string> $sensitiveKeys */
    public function __construct(
        private array $sensitiveKeys = ['authorization', 'cookie', 'password', 'passwd', 'secret', 'token', 'access_token', 'refresh_token', 'credential'],
        private int $maxDepth = 8,
    ) {
    }

    /** @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        return $this->walk($context, 0);
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function walk(array $values, int $depth): array
    {
        if ($depth >= $this->maxDepth) {
            return ['_truncated' => true];
        }
        foreach ($values as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                $values[$key] = '[REDACTED]';
            } elseif (\is_array($value)) {
                $values[$key] = $this->walk($value, $depth + 1);
            } elseif (\is_string($value) && \strlen($value) > 4096) {
                $values[$key] = substr($value, 0, 4096).'…';
            }
        }

        return $values;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '.'], '_', $key));

        return array_any($this->sensitiveKeys, static fn (string $sensitive): bool => $normalized === $sensitive || str_ends_with($normalized, '_'.$sensitive));
    }
}
