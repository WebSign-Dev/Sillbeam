<?php

declare(strict_types=1);

namespace Nexum\Management;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexumManagementBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
