<?php

declare(strict_types=1);

namespace Sillbeam\Core\Kernel\Runtime;

use Composer\InstalledVersions;
use Sillbeam\Core\Kernel\SillbeamKernel;
use Sillbeam\Core\Module\ModuleRegistryInterface;
use Symfony\Component\HttpKernel\Kernel;

final readonly class KernelInformation implements KernelInformationInterface
{
    public function __construct(
        private Kernel $kernel,
        private ModuleRegistryInterface $modules,
    ) {
    }

    public function all(): array
    {
        return [
            'application' => $this->kernel instanceof SillbeamKernel
                ? $this->kernel->getApplicationName()
                : basename($this->kernel->getProjectDir()),
            'environment' => $this->kernel->getEnvironment(),
            'debug' => $this->kernel->isDebug() ? 'yes' : 'no',
            'php' => \PHP_VERSION,
            'symfony' => Kernel::VERSION,
            'sillbeam' => InstalledVersions::isInstalled('websign-dev/sillbeam-core')
                ? (InstalledVersions::getPrettyVersion('websign-dev/sillbeam-core') ?? 'unknown')
                : 'source',
            'modules' => \count($this->modules->all()),
            'modules_enabled' => \count($this->modules->active()),
        ];
    }
}
