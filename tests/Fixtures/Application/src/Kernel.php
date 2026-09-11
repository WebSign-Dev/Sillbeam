<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getApplicationName(): string
    {
        return 'Nexum Test Application';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 4).'/.phpunit.cache/application/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 4).'/.phpunit.cache/application/log';
    }
}
