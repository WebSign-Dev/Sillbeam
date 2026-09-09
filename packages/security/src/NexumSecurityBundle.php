<?php

declare(strict_types=1);

namespace Nexum\Security;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexumSecurityBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
