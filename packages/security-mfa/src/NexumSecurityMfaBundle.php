<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexumSecurityMfaBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
