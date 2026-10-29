<?php

declare(strict_types=1);

namespace Sillbeam\Core\Kernel;

use Composer\InstalledVersions;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Optional Symfony kernel with MicroKernelTrait and Composer-based application naming.
 *
 * Standard Symfony kernels remain supported; extending this class is optional.
 *
 * @api
 */
abstract class SillbeamKernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Returns the root Composer package name, falling back to the project directory basename.
     */
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
