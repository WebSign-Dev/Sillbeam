<?php

declare(strict_types=1);

namespace Nexum\Kernel;

use Composer\InstalledVersions;
use Nexum\Kernel\Module\YamlModuleRegistry;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

abstract class NexumKernel extends BaseKernel
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

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configurationDirectory = $this->getProjectDir().'/config';

        if (is_dir($configurationDirectory.'/routes')) {
            $routes->import($configurationDirectory.'/routes/*.{php,yaml}');
        }

        if (is_file($configurationDirectory.'/routes.yaml')) {
            $routes->import($configurationDirectory.'/routes.yaml');
        }

        $legacyControllers = $this->getProjectDir().'/src/Controller';

        if (is_dir($legacyControllers)) {
            $routes->import($legacyControllers.'/', 'attribute');
        }

        $registry = new YamlModuleRegistry($configurationDirectory.'/nexum/modules.yaml');

        foreach ($registry->enabled() as $module) {
            $routes->import($module->directory.'/', 'attribute');
        }
    }
}
