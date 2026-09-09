<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

final class AccessTokenHasher
{
    public function hash(#[\SensitiveParameter] string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
