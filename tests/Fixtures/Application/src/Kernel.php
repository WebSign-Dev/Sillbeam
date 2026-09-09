<?php

declare(strict_types=1);

namespace App;

use Nexum\Kernel\NexumKernel;

final class Kernel extends NexumKernel
{
    public function getApplicationName(): string
    {
        return 'Nexum Test Application';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }
}
