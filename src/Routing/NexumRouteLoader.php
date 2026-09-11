<?php

declare(strict_types=1);

namespace Nexum\Routing;

use Nexum\Module\ModuleDefinition;
use Nexum\Module\ModuleRegistryInterface;
use Nexum\Project\ProjectDefinition;
use Nexum\Project\ProjectRegistryInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class NexumRouteLoader extends Loader
{
    private bool $loaded = false;

    /**
     * @param list<class-string>                      $applicationModules
     * @param array<class-string, list<class-string>> $projectModules
     * @param array<string, mixed>                    $config
     */
    public function __construct(
        private readonly AttributeDirectoryLoader $attributes,
        private readonly PhpFileLoader $php,
        private readonly ModuleRegistryInterface $moduleRegistry,
        private readonly ProjectRegistryInterface $projectRegistry,
        private readonly array $applicationModules,
        private readonly array $projectModules,
        private readonly array $config,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \LogicException('The Nexum route loader can only be loaded once.');
        }
        $this->loaded = true;
        $routes = new RouteCollection();
        $modules = $this->moduleRegistry->all();
        $projects = $this->projectRegistry->all();
        if ([] === $projects) {
            foreach ($this->applicationModules as $moduleClass) {
                $this->addModule($routes, $modules[$moduleClass]);
            }
            $this->addExposedApis($routes, null);
        } else {
            foreach ($projects as $projectClass => $project) {
                $projectRoutes = new RouteCollection();
                foreach ($project->controllerDirectories as $directory) {
                    $this->addControllerDirectory($projectRoutes, $directory);
                }
                foreach ($this->projectModules[$projectClass] as $moduleClass) {
                    $this->addModule($projectRoutes, $modules[$moduleClass]);
                }
                $this->addExposedApis($projectRoutes, $project);
                $projectRoutes->addNamePrefix($project->id.'.');
                if ([] !== $project->hosts) {
                    $projectRoutes->setHost('{_nexum_host}', ['_nexum_host' => $project->canonicalHost()], [
                        '_nexum_host' => implode('|', array_map(static fn (string $host): string => preg_quote($host, '#'), $project->hosts)),
                    ]);
                }
                $projectRoutes->addResource(new FileResource($project->descriptorFile));
                $routes->addCollection($projectRoutes);
            }
        }
        foreach (glob($this->projectDir.'/config/packages/nexum*.{yaml,yml,php}', \GLOB_BRACE) ?: [] as $file) {
            $routes->addResource(new FileResource($file));
        }
        foreach ([$this->projectDir.'/src/Module', $this->projectDir.'/src/Project'] as $directory) {
            if (is_dir($directory)) {
                $routes->addResource(new DirectoryResource($directory));
            }
        }
        if (is_dir($this->projectDir.'/config/packages')) {
            $routes->addResource(new DirectoryResource($this->projectDir.'/config/packages', '/^nexum.*\.(?:ya?ml|php)$/'));
        }

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'nexum' === $type;
    }

    private function addModule(RouteCollection $routes, ModuleDefinition $module): void
    {
        foreach ($module->controllerDirectories as $directory) {
            $this->addControllerDirectory($routes, $directory);
        }
        $routes->addResource(new FileResource($module->descriptorFile));
    }

    private function addControllerDirectory(RouteCollection $routes, string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $collection = $this->attributes->load($directory, 'attribute');
        if (null !== $collection) {
            $routes->addCollection($collection);
        }
        $routes->addResource(new DirectoryResource($directory));
    }

    private function addExposedApis(RouteCollection $routes, ?ProjectDefinition $project): void
    {
        $apis = [
            [$this->config['security']['api'], \dirname(__DIR__).'/Security/config/routes.php', '/api/auth'],
            [$this->config['security']['mfa']['api'], \dirname(__DIR__).'/Security/Mfa/config/routes.php', '/api/auth/mfa'],
            [$this->config['security']['passkeys']['api'], \dirname(__DIR__).'/Security/Passkey/config/routes.php', '/api/auth/passkeys'],
            [$this->config['management']['api'], \dirname(__DIR__).'/Management/config/routes.php', '/api/nexum'],
        ];
        foreach ($apis as [$api, $file, $prefix]) {
            if (!$api['enabled'] || !$this->isExposedFor($api['projects'], $project)) {
                continue;
            }
            $collection = $this->php->load($file, 'php');
            $collection->addPrefix($prefix);
            foreach ($collection as $route) {
                if ('/' !== $route->getPath()) {
                    $route->setPath(rtrim($route->getPath(), '/'));
                }
            }
            $routes->addCollection($collection);
        }
    }

    /** @param list<string> $selected */
    private function isExposedFor(array $selected, ?ProjectDefinition $project): bool
    {
        if (null === $project) {
            return true;
        }

        return ['*'] === $selected || \in_array($project->id, $selected, true);
    }
}
