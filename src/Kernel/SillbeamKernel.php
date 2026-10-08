<?php

declare(strict_types=1);

namespace Sillbeam\Kernel;

use Composer\InstalledVersions;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

abstract class SillbeamKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getApplicationName(): string
    {
        $rootPackage = InstalledVersions::getRootPackage();
        $packageName = $rootPackage['pretty_name'] ?? $rootPackage['name'] ?? null;

        if (\is_string($packageName) && '' !== trim($packageName)) {
            return $packageName;
        }

        return basename($this->getProjectDir());
    }
}
