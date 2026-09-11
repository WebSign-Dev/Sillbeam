<?php

declare(strict_types=1);

namespace App;

use Nexum\Kernel\NexumKernel;

final class NexumTestKernel extends NexumKernel
{
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 4).'/.phpunit.cache/nexum-kernel-application/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 4).'/.phpunit.cache/nexum-kernel-application/log';
    }
}
