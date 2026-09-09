<?php

declare(strict_types=1);

namespace Nexum\User;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexumUserBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
