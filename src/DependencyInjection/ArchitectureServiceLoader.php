<?php

declare(strict_types=1);

namespace Sillbeam\Core\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Registers autowired area services through Symfony and defers interface aliases across areas.
 *
 * @internal
 */
final class ArchitectureServiceLoader
{
    private ?YamlFileLoader $loader = null;
    private ?ContainerBuilder $container = null;

    /**
     * Registers services in the supplied container; missing directories are skipped.
     *
     * Switching containers finalizes pending aliases. With deferAliases enabled the caller
     * must invoke finish() after all areas have been registered.
     *
     * @param list<string>|null $excludes
     *
     * @throws \LogicException If kernel.environment is not a string or the loader is unavailable.
     */
    public function load(ContainerBuilder $container, string $directory, string $namespace, string $descriptorFile, bool $excludeNestedModules = false, ?array $excludes = null, bool $deferAliases = false): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $excludes ??= [$directory.'/**/{Entity,Dto,Tests,Fixtures,Migrations}/'];
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

    /**
     * Registers aliases for interfaces with a single implementation across loaded areas.
     */
    public function finish(): void
    {
        $this->loader?->registerAliasesForSinglyImplementedInterfaces();
    }
}
