<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

final readonly class SecondFactorChallenge
{
    /** @param list<string> $methods */
    public function __construct(
        public string $id,
        public array $methods,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
