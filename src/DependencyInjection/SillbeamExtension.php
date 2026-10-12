<?php

declare(strict_types=1);

namespace Sillbeam\DependencyInjection;

use Sillbeam\Architecture\ArchitectureCompiler;
use Sillbeam\Architecture\ArchitectureDiscovery;
use Sillbeam\Architecture\CodeDependencyAnalyzer;
use Sillbeam\Architecture\CompiledArchitecture;
use Sillbeam\Architecture\Console\ArchitectureCommand;
use Sillbeam\Architecture\DiscoveryOptions;
use Sillbeam\Architecture\DiscoveryOptionsResolver;
use Sillbeam\Architecture\DiscoveryResult;
use Sillbeam\Architecture\PathMatcher;
use Sillbeam\Architecture\SourceInspector;
use Sillbeam\Kernel\Command\AboutCommand;
use Sillbeam\Kernel\Runtime\KernelInformation;
use Sillbeam\Kernel\Runtime\KernelInformationInterface;
use Sillbeam\Module\ModuleDefinition;
use Sillbeam\Module\ModuleRegistry;
use Sillbeam\Module\ModuleRegistryInterface;
use Sillbeam\Project\ProjectContext;
use Sillbeam\Project\ProjectContextInterface;
use Sillbeam\Project\ProjectRegistry;
use Sillbeam\Project\ProjectRegistryInterface;
use Sillbeam\Routing\SillbeamRouteLoader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;

final class SillbeamExtension extends Extension implements PrependExtensionInterface
{
    private ?CompiledArchitecture $architecture = null;
    private ?DiscoveryResult $discovery = null;
    private ?DiscoveryOptions $options = null;
    private ?SourceInspector $inspector = null;
    /** @var array<string, mixed> */
    private array $config = [];
    /** @var list<array{prefix: string, directory: string, excludes: list<string>}> */
    private array $doctrineMappings = [];

    /** @var list<string> */
    private array $configurationResources = [];

    public function prepend(ContainerBuilder $container): void
    {
        foreach ($container->getResources() as $resource) {
            if ($resource instanceof FileResource) {
                $this->configurationResources[] = $resource->getResource();
            }
        }
        $this->build($container->getExtensionConfig($this->getAlias()), $container);
        if ($this->config['doctrine']['enabled']) {
            $this->prependDoctrine($container);
        }
    }

    /** @param array<array-key, array<string, mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        if (null === $this->architecture) {
            $this->build($configs, $container);
        }
        $architecture = $this->architecture ?? throw new \LogicException('Sillbeam architecture was not compiled.');
        $discovery = $this->discovery ?? throw new \LogicException('Sillbeam discovery was not run.');
        foreach ($discovery->resources as $resource) {
            $container->addResource(is_dir($resource) ? new DirectoryResource($resource, '/\\.php$/') : (is_file($resource) ? new FileResource($resource) : new FileExistenceResource($resource)));
        }
        $projectDir = $container->getParameter('kernel.project_dir');
        if (!\is_string($projectDir)) {
            throw new \LogicException('kernel.project_dir must be a string.');
        }
        $composer = $projectDir.'/composer.json';
        $container->addResource(is_file($composer) ? new FileResource($composer) : new FileExistenceResource($composer));
        // Also detect newly added configuration, including the first sillbeam.yaml.
        $configurationDirectory = $projectDir.'/config';
        $container->addResource(is_dir($configurationDirectory) ? new DirectoryResource($configurationDirectory) : new FileExistenceResource($configurationDirectory));
        $modules = array_map(static fn ($module): array => get_object_vars($module), $architecture->modules);
        $projects = array_map(static fn ($project): array => get_object_vars($project), $architecture->projects);
        $container->register('sillbeam.module_registry', ModuleRegistry::class)->setArguments([$modules, $architecture->applicationModules, $architecture->projectModules]);
        $container->setAlias(ModuleRegistryInterface::class, 'sillbeam.module_registry')->setPublic(true);
        $container->register('sillbeam.project_registry', ProjectRegistry::class)->setArguments([$projects]);
        $container->setAlias(ProjectRegistryInterface::class, 'sillbeam.project_registry')->setPublic(true);
        $container->autowire(ProjectContext::class)->setArguments([new Reference('request_stack'), new Reference(ProjectRegistryInterface::class), new Reference(ModuleRegistryInterface::class)]);
        $container->setAlias(ProjectContextInterface::class, ProjectContext::class)->setPublic(true);
        $container->register(SillbeamRouteLoader::class)->setArguments([
            new Reference('routing.loader.attribute.file'), new Reference(ModuleRegistryInterface::class),
            new Reference(ProjectRegistryInterface::class), $discovery->resources, $this->controllerFiles($architecture),
            array_values(array_unique([...$this->configurationResources, $projectDir.'/config', $composer])),
        ])->addTag('routing.loader', ['priority' => 100]);
        $this->loadServices($container, $architecture);
        $container->autowire(KernelInformation::class)->setArguments([new Reference('kernel'), new Reference(ModuleRegistryInterface::class)]);
        $container->setAlias(KernelInformationInterface::class, KernelInformation::class);
        $container->autowire(AboutCommand::class)->setAutoconfigured(true);
        $container->autowire(ArchitectureCommand::class)->setAutoconfigured(true);
        $container->setParameter('sillbeam.doctrine.mappings', $this->doctrineMappings);
    }

    /** @param array<array-key, array<string, mixed>> $configs */
    private function build(array $configs, ContainerBuilder $container): void
    {
        $configs = $container->resolveEnvPlaceholders($container->getParameterBag()->resolveValue($configs), true);
        $config = $this->processConfiguration(new Configuration(), $configs);
        $config = $container->getParameterBag()->resolveValue($config);
        $hasDoctrine = $container->hasExtension('doctrine');
        $config['doctrine']['enabled'] ??= $hasDoctrine;
        if ($config['doctrine']['enabled'] && !$hasDoctrine) {
            throw new \LogicException('sillbeam.doctrine is enabled, but DoctrineBundle is not registered.');
        }
        $this->config = $config;
        $this->options = (new DiscoveryOptionsResolver())->resolve($container, $configs, $config);
        $this->inspector = new SourceInspector();
        $this->discovery = (new ArchitectureDiscovery($this->inspector))->discover($this->options);
        $architecture = (new ArchitectureCompiler())->compile($this->discovery, $config);
        $references = (new CodeDependencyAnalyzer($this->inspector))->analyze($this->discovery, $architecture, $this->options);
        $modules = [];
        foreach ($architecture->modules as $class => $module) {
            $data = get_object_vars($module);
            $data['dependencyReferences'] = $references[$class] ?? [];
            $data['observedDependencies'] = array_values(array_unique(array_column($references[$class] ?? [], 'module')));
            $data['activationReasons'] = $this->activationReasons($class, $architecture);
            $modules[$class] = new ModuleDefinition(...$data);
        }
        $this->architecture = new CompiledArchitecture($modules, $architecture->projects, $architecture->applicationModules, $architecture->projectModules);
    }

    /** @return list<string> */
    private function activationReasons(string $class, CompiledArchitecture $architecture): array
    {
        $reasons = [];
        if (\in_array($class, $architecture->applicationModules, true)) {
            $reasons[] = 'application';
        }
        foreach ($architecture->projects as $project) {
            if (\in_array($class, $project->declaredModules, true)) {
                $reasons[] = 'project:'.$project->id.':declared';
            }
            if ($architecture->modules[$class]->projectClass === $project->class && $architecture->modules[$class]->enabled) {
                $reasons[] = 'project:'.$project->id.':private';
            }
        }
        foreach ($architecture->usedModules() as $source) {
            if (\in_array($class, $architecture->modules[$source]->dependencies, true)) {
                $reasons[] = 'dependency:'.$source;
            }
        }

        return array_values(array_unique($reasons));
    }

    private function prependDoctrine(ContainerBuilder $container): void
    {
        $architecture = $this->architecture ?? throw new \LogicException('Sillbeam architecture was not compiled.');
        $mappings = [];
        $definitions = array_values($architecture->projects);
        foreach ($architecture->usedModules() as $class) {
            $definitions[] = $architecture->modules[$class];
        }
        foreach ($definitions as $definition) {
            foreach ($definition->entityDirectories as $directory) {
                $relative = trim(substr($directory, \strlen($definition->directory)), '/');
                $prefix = $definition->namespace.('' === $relative ? '' : '\\'.str_replace('/', '\\', $relative));
                $key = 'Sillbeam'.hash('xxh128', $directory);
                $this->doctrineMappings[] = ['prefix' => $prefix, 'directory' => $directory, 'excludes' => $this->excludedMappingFiles($directory, $definition->class, $architecture)];
                $mappings[$key] = ['type' => 'attribute', 'is_bundle' => false, 'dir' => $directory, 'prefix' => $prefix, 'alias' => $key];
            }
        }
        if ([] !== $mappings) {
            $container->prependExtensionConfig('doctrine', ['orm' => ['mappings' => $mappings]]);
        }
    }

    /** @return array<string, list<string>> */
    private function controllerFiles(CompiledArchitecture $architecture): array
    {
        $files = $this->inspector?->files($this->options ?? throw new \LogicException('Missing discovery options.')) ?? [];
        $result = [];
        $definitions = array_values($architecture->projects);
        foreach ($architecture->usedModules() as $class) {
            $definitions[] = $architecture->modules[$class];
        }
        foreach ($definitions as $definition) {
            $result[$definition->class] = [];
            foreach ($files as $file) {
                if (!$this->belongsToArea($file->path, $definition->class, $architecture)) {
                    continue;
                }
                foreach ($definition->controllerDirectories as $directory) {
                    if (str_starts_with($file->path, $directory.'/')) {
                        $result[$definition->class][] = $file->path;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    private function belongsToArea(string $path, string $class, CompiledArchitecture $architecture): bool
    {
        $definition = $architecture->modules[$class] ?? $architecture->projects[$class];
        if (!str_starts_with($path, $definition->directory.'/')) {
            return false;
        }
        foreach ($architecture->modules as $module) {
            if ($module->class !== $class && str_starts_with($path, $module->directory.'/')) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private function excludedMappingFiles(string $directory, string $class, CompiledArchitecture $architecture): array
    {
        $options = $this->options ?? throw new \LogicException('Missing discovery options.');
        $files = ($this->inspector ?? throw new \LogicException('Missing source index.'))->files($options);
        $excludes = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            if (!isset($files[$file->getPathname()]) || !$this->belongsToArea($file->getPathname(), $class, $architecture)) {
                $excludes[] = $file->getPathname();
            }
        }

        return $excludes;
    }

    private function loadServices(ContainerBuilder $container, CompiledArchitecture $architecture): void
    {
        $options = $this->options ?? throw new \LogicException('Sillbeam discovery options are missing.');
        $files = ($this->inspector ?? throw new \LogicException('Sillbeam source index is missing.'))->files($options);
        $loader = new ArchitectureServiceLoader();
        $definitions = array_values($architecture->projects);
        foreach ($architecture->usedModules() as $class) {
            $definitions[] = $architecture->modules[$class];
        }
        foreach ($definitions as $definition) {
            $excludes = [];
            foreach ($architecture->modules as $module) {
                if ($module->class !== $definition->class && str_starts_with($module->directory.'/', $definition->directory.'/')) {
                    $excludes[] = $module->directory.'/';
                }
            }
            foreach ($files as $file) {
                if (!str_starts_with($file->path, $definition->directory.'/')) {
                    continue;
                }
                $relative = substr($file->path, \strlen($definition->directory) + 1);
                if ($options->excluded($file->path) || PathMatcher::matches($relative, $options->serviceExcludes)) {
                    $excludes[] = $file->path;
                }
            }
            // Propagate the source index's pruned paths even through enclosing Project imports.
            foreach ($this->inspector->excludedPaths($options) as $path) {
                if (str_starts_with($path, $definition->directory.'/')) {
                    $excludes[] = $path;
                }
            }
            $loader->load($container, $definition->directory, $definition->namespace, $definition->descriptorFile, false, $excludes, true);
        }
        $loader->finish();
    }
}
