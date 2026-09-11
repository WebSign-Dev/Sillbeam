<?php

declare(strict_types=1);

namespace Nexum\Architecture;

final readonly class DiscoveryOptions
{
    public function __construct(public string $root, public string $namespace)
    {
    }
}
