<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

final readonly class IssuedAccessToken
{
    public function __construct(
        #[\SensitiveParameter]
        public string $plainToken,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
