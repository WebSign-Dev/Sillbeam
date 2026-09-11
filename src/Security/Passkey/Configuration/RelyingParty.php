<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Configuration;

final readonly class RelyingParty
{
    /** @param list<string> $allowedOrigins */
    public function __construct(public string $id, public array $allowedOrigins)
    {
    }
}
