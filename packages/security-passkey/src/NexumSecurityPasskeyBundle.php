<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexumSecurityPasskeyBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
