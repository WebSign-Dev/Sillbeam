<?php

declare(strict_types=1);

namespace App;

use Sillbeam\Core\Kernel\SillbeamKernel;

final class SillbeamTestKernel extends SillbeamKernel
{
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return \dirname(__DIR__, 4).'/.phpunit.cache/sillbeam-kernel-application/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return \dirname(__DIR__, 4).'/.phpunit.cache/sillbeam-kernel-application/log';
    }
}
