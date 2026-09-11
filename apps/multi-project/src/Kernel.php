<?php

declare(strict_types=1);

namespace ProjectApp;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 3).'/.phpunit.cache/project-application/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 3).'/.phpunit.cache/project-application/log';
    }
}
