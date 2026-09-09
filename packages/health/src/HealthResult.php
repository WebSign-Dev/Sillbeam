<?php

declare(strict_types=1);

namespace Nexum\Health;

final readonly class HealthResult
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(public string $name, public HealthStatus $status, public array $metadata = [])
    {
    }
}
