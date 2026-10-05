<?php

declare(strict_types=1);

namespace Nexum\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class ArchitectureServiceLoader
{
    private ?YamlFileLoader $loader = null;
    private ?ContainerBuilder $container = null;

    /** @param list<string>|null $excludes */
    public function load(ContainerBuilder $container, string $directory, string $namespace, string $descriptorFile, bool $excludeNestedModules = false, ?array $excludes = null, bool $deferAliases = false): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $excludes ??= [$directory.'/**/{Entity,Dto}/'];
        $environment = $container->hasParameter('kernel.environment') ? $container->getParameter('kernel.environment') : null;
        if (null !== $environment && !\is_string($environment)) {
            throw new \LogicException('kernel.environment must be a string.');
        }
        if ($this->container !== $container) {
            $this->finish();
            $this->container = $container;
            $this->loader = new YamlFileLoader($container, new FileLocator(), $environment);
        }
        $loader = $this->loader ?? throw new \LogicException('Missing service loader.');
        $prototype = (new Definition())->setAutowired(true)->setAutoconfigured(true);
        if (is_file($descriptorFile)) {
            $excludes[] = $descriptorFile;
        }
        if ($excludeNestedModules) {
            $excludes[] = $directory.'/Module/';
        }
        $loader->registerClasses($prototype, rtrim($namespace, '\\').'\\', $directory.'/', $excludes);
        if (!$deferAliases) {
            $this->finish();
        }
    }

    public function finish(): void
    {
        $this->loader?->registerAliasesForSinglyImplementedInterfaces();
    }
}
