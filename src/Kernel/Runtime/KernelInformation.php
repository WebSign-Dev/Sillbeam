<?php

declare(strict_types=1);

namespace Nexum\Kernel\Runtime;

use Composer\InstalledVersions;
use Nexum\Kernel\NexumKernel;
use Nexum\Module\ModuleRegistryInterface;
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
            'application' => $this->kernel instanceof NexumKernel
                ? $this->kernel->getApplicationName()
                : basename($this->kernel->getProjectDir()),
            'environment' => $this->kernel->getEnvironment(),
            'debug' => $this->kernel->isDebug() ? 'yes' : 'no',
            'php' => \PHP_VERSION,
            'symfony' => Kernel::VERSION,
            'nexum' => InstalledVersions::isInstalled('benjaminoeffinger/nexum')
                ? (InstalledVersions::getPrettyVersion('benjaminoeffinger/nexum') ?? 'unknown')
                : 'source',
            'modules' => \count($this->modules->all()),
            'modules_enabled' => \count($this->modules->active()),
        ];
    }
}
