<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console\Doctor;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Sillbeam\Core\Architecture\DiscoveryOptionsResolver;
use Sillbeam\Core\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/** Static evidence only. Never includes application PHP or prepares a kernel. */
final class InstallationInspector
{
    /** @return list<array{label: string, status: string, detail: string}> */
    public function inspect(string $directory, string $environment, ?string $kernelFile = null): array
    {
        $checks = [];
        $add = static function (string $label, string $status, string $detail = '') use (&$checks): void {
            $checks[] = compact('label', 'status', 'detail');
        };
        foreach (['Bundle registration', 'Console adapter'] as $label) {
            try {
                $nodes = $this->php($directory.('Bundle registration' === $label ? '/config/bundles.php' : '/bin/console'));
                if ('Bundle registration' === $label) {
                    if (1 !== \count($nodes) || !$nodes[0] instanceof Node\Stmt\Return_ || !$nodes[0]->expr instanceof Node\Expr\Array_) {
                        throw new \RuntimeException('config/bundles.php is dynamic.');
                    }
                    $enabled = false;
                    foreach ($nodes[0]->expr->items as $item) {
                        if (null === $item || !$item->key instanceof Node\Expr\ClassConstFetch || !$item->key->class instanceof Node\Name || !$item->key->name instanceof Node\Identifier || 'class' !== strtolower($item->key->name->toString()) || $item->unpack || !$item->value instanceof Node\Expr\Array_) {
                            throw new \RuntimeException('Non-literal bundle declaration.');
                        }
                        $flags = [];
                        foreach ($item->value->items as $flag) {
                            if (null === $flag || !$flag->key instanceof Node\Scalar\String_ || !$flag->value instanceof Node\Expr\ConstFetch || !\in_array(strtolower($flag->value->name->toString()), ['true', 'false'], true)) {
                                throw new \RuntimeException('Dynamic bundle environment flag.');
                            }
                            $flags[$flag->key->value] = 'true' === strtolower($flag->value->name->toString());
                        }
                        if ('Sillbeam\\Core\\SillbeamCoreBundle' === $item->key->class->toString()) {
                            $enabled = $flags[$environment] ?? $flags['all'] ?? false;
                        }
                    }
                    $add($label, $enabled ? 'verified' : 'action required', $enabled ? '' : 'Register Sillbeam\\Core\\SillbeamCoreBundle::class for this environment in config/bundles.php.');
                } else {
                    $adapter = $this->consoleAdapter($nodes);
                    if ('Sillbeam\\Core\\Console\\Application' === $adapter) {
                        $add($label, 'verified');
                    } elseif ('Symfony\\Bundle\\FrameworkBundle\\Console\\Application' === $adapter) {
                        $add($label, 'action required', 'In bin/console use Sillbeam\\Core\\Console\\Application; to keep doctor available before container compilation. Preserve the existing bootstrap and Kernel.');
                    } else {
                        throw new \RuntimeException('bin/console uses a dynamic or Runtime callback bootstrap. Manually verify use Sillbeam\\Core\\Console\\Application; and its construction.');
                    }
                }
            } catch (\Throwable $error) {
                $add($label, 'not verified', $error->getMessage().' Review this file manually.');
            }
        }
        try {
            $nodes = $this->php($kernelFile ?? $directory.'/src/Kernel.php');
            $classes = (new NodeFinder())->findInstanceOf($nodes, Node\Stmt\Class_::class);
            $class = $classes[0] ?? null;
            if (1 !== \count($classes) || null === $class?->extends || 'Symfony\\Component\\HttpKernel\\Kernel' !== $class->extends->toString() || [] !== $class->getMethods()) {
                throw new \RuntimeException('Custom or unavailable Kernel loading hooks.');
            }
            $traits = (new NodeFinder())->findInstanceOf($class->stmts, Node\Stmt\TraitUse::class);
            if (1 !== \count($traits) || 1 !== \count($traits[0]->traits) || 'Symfony\\Bundle\\FrameworkBundle\\Kernel\\MicroKernelTrait' !== $traits[0]->traits[0]->toString() || [] !== $traits[0]->adaptations) {
                throw new \RuntimeException('Unknown Kernel configuration loading.');
            }
            $add('Configuration loading convention', 'verified');
        } catch (\Throwable $error) {
            $add('Configuration loading convention', 'not verified', $error->getMessage().' Manually verify which bundle, route, service and discovery files the selected Kernel loads.');
        }
        $documents = [];
        $unknown = false;
        try {
            $configDir = $directory.'/config';
            if (!is_dir($configDir)) {
                throw new \RuntimeException('Missing config directory.');
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($configDir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                $path = $file->getPathname();
                $relative = substr($path, \strlen($configDir) + 1);
                if ($file->isLink() && $file->isDir() && preg_match('~^(?:packages|routes)(?:/'.preg_quote($environment, '~').')?$~D', $relative)) {
                    $unknown = true;
                }
                $serviceExtension = is_file($configDir.'/services.yaml') ? 'yaml' : 'php';
                $routeExtension = is_file($configDir.'/routes.yaml') ? 'yaml' : 'php';
                $selected = \in_array($relative, ['services.'.$serviceExtension, 'services_'.$environment.'.'.$serviceExtension, 'routes.'.$routeExtension], true)
                    || 1 === preg_match('~^(?:packages|routes)/(?:'.preg_quote($environment, '~').'/)?[^/]+\\.(?:yaml|php)$~D', $relative);
                if (!$selected) {
                    continue;
                }
                if ($file->isLink() || !$file->isFile()) {
                    $unknown = true;
                    continue;
                }
                if (!\in_array($file->getExtension(), ['yaml', 'yml'], true)) {
                    $unknown = true;
                    continue;
                }
                $data = Yaml::parse($this->read($path));
                if (null === $data) {
                    $data = [];
                }
                if (!\is_array($data)) {
                    throw new \RuntimeException('Expected YAML mapping in '.$relative);
                }
                foreach ($data as $key => $overlay) {
                    if (\is_string($key) && str_starts_with($key, 'when@')) {
                        unset($data[$key]);
                        if ('when@'.$environment === $key) {
                            // Avoid guessing Symfony merge/override order.
                            $unknown = true;
                        }
                    }
                }
                if (isset($data['services']) && !\is_array($data['services'])) {
                    throw new \RuntimeException('Invalid services mapping in '.$relative);
                }
                if (isset($data['imports'])) {
                    $unknown = true;
                }
                $documents[$path] = $data;
            }
        } catch (\Throwable $error) {
            $unknown = true;
            $add('Configuration files', 'not verified', $error->getMessage().' Review configuration manually.');
        }
        $route = false;
        $routeUnknown = $unknown;
        $routeNames = [];
        foreach ($documents as $path => $data) {
            if (!preg_match('~/config/routes(?:/|\.ya?ml$)~', $path)) {
                continue;
            }
            foreach ($data as $name => $entry) {
                if (isset($routeNames[$name])) {
                    $routeUnknown = true;
                }
                $routeNames[$name] = true;
                if (!\is_array($entry)) {
                    $routeUnknown = true;
                } elseif ('sillbeam' === ($entry['type'] ?? null)) {
                    // The loader ignores resource; require an import rather than a route definition.
                    if (isset($entry['resource']) && !isset($entry['path']) && ['resource', 'type'] === array_values(array_intersect(['resource', 'type'], array_keys($entry))) && 2 === \count($entry)) {
                        $route = true;
                    } else {
                        $routeUnknown = true;
                    }
                } elseif (isset($entry['resource']) && 'attribute' !== ($entry['type'] ?? null)) {
                    $routeUnknown = true;
                }
            }
        }
        $add('Route import', $routeUnknown ? 'not verified' : ($route ? 'verified' : 'action required'), $routeUnknown ? 'Review route imports and options manually; dynamic/imported configuration is unresolved.' : ($route ? '' : 'Add a Sillbeam import in config/routes.yaml: resource: . and type: sillbeam.'));
        $options = null;
        try {
            if ($unknown) {
                throw new \RuntimeException('Dynamic files, imports or environment overlays may change discovery.');
            }
            $configs = [];
            $discoveryDeclarations = 0;
            foreach ($documents as $path => $data) {
                if (!preg_match('~/config/routes(?:/|\.yaml$)~', $path) && isset($data['sillbeam'])) {
                    $configs[] = $data['sillbeam'];
                    if (isset($data['sillbeam']['discovery']) && ++$discoveryDeclarations > 1) {
                        throw new \RuntimeException('Multiple discovery declarations require manual merge-order verification.');
                    }
                }
            }
            $config = (new Processor())->processConfiguration(new Configuration(), $configs);
            if (preg_match('/%[^%]+%/', str_replace('%kernel.project_dir%', $directory, json_encode($config, \JSON_THROW_ON_ERROR)))) {
                throw new \RuntimeException('Unresolved discovery parameters.');
            }
            $container = new ContainerBuilder();
            $container->setParameter('kernel.project_dir', $directory);
            $options = (new DiscoveryOptionsResolver())->resolve($container, $configs, $config);
            $add('Discovery configuration', 'verified');
        } catch (\Throwable $error) {
            $add('Discovery configuration', 'not verified', $error->getMessage().' Manually check discovery roots, namespaces and Composer mappings.');
        }
        $serviceUnknown = $unknown || null === $options;
        $overlaps = [];
        $serviceIds = [];
        foreach ($documents as $path => $data) {
            foreach ($data['services'] ?? [] as $id => $service) {
                if (isset($serviceIds[$id]) && !str_starts_with((string) $id, '_')) {
                    $serviceUnknown = true;
                }
                $serviceIds[$id] = true;
                if (!\is_array($service) || !isset($service['resource'])) {
                    if (!str_starts_with((string) $id, '_')) {
                        $serviceUnknown = true;
                    }
                    continue;
                }
                $resource = $this->directory($service['resource'], \dirname($path), $directory);
                if (null === $resource) {
                    $serviceUnknown = true;
                    continue;
                }
                foreach (null === $options ? [] : [...$options->moduleRoots, ...$options->projectRoots] as $root) {
                    if (!str_starts_with($root->path.'/', rtrim($resource, '/').'/') && !str_starts_with(rtrim($resource, '/').'/', $root->path.'/')) {
                        continue;
                    }
                    $excluded = false;
                    foreach ((array) ($service['exclude'] ?? []) as $exclude) {
                        $excludePath = $this->directory($exclude, \dirname($path), $directory);
                        if (null === $excludePath) {
                            $serviceUnknown = true;
                        } elseif (str_starts_with($root->path.'/', rtrim($excludePath, '/').'/') || str_starts_with(rtrim($resource, '/').'/', rtrim($excludePath, '/').'/')) {
                            $excluded = true;
                        }
                    }
                    if (!$excluded) {
                        $overlaps[] = substr($path, \strlen($directory) + 1).': '.$id;
                    }
                }
            }
        }
        $add('Service imports', $serviceUnknown ? 'not verified' : ([] === $overlaps ? 'verified' : 'action required'), [] !== $overlaps ? implode(', ', array_unique($overlaps)).($serviceUnknown ? ' may bypass activation; verify imports and overrides manually. Exclude' : ' can bypass activation. Exclude').' complete module and Project discovery areas from Symfony resource imports; let Sillbeam register their services.' : ($serviceUnknown ? 'Manually check service resources, excludes and explicit definitions against module/Project areas.' : ''));

        return $checks;
    }

    /** @param array<Node> $nodes */
    private function consoleAdapter(array $nodes): ?string
    {
        // Accept the conventional final assignment/run pair only. No data-flow guessing.
        $run = array_pop($nodes);
        $assignment = array_pop($nodes);
        if (!$run instanceof Node\Stmt\Expression || !$run->expr instanceof Node\Expr\MethodCall || !$run->expr->name instanceof Node\Identifier || 'run' !== $run->expr->name->toString() || !$run->expr->var instanceof Node\Expr\Variable
            || !$assignment instanceof Node\Stmt\Expression || !$assignment->expr instanceof Node\Expr\Assign || !$assignment->expr->var instanceof Node\Expr\Variable || !$assignment->expr->expr instanceof Node\Expr\New_ || !$assignment->expr->expr->class instanceof Node\Name
            || !\is_string($run->expr->var->name) || $run->expr->var->name !== $assignment->expr->var->name) {
            return null;
        }
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse || $node instanceof Node\Stmt\Declare_ || $node instanceof Node\Stmt\Nop) {
                continue;
            }
            if (!$node instanceof Node\Stmt\Expression || !($node->expr instanceof Node\Expr\Assign || $node->expr instanceof Node\Expr\Include_)) {
                return null;
            }
        }

        return $assignment->expr->expr->class->toString();
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException('Cannot read '.$path);
        }

        return $contents;
    }

    /** @return array<Node> */
    private function php(string $path): array
    {
        $nodes = (new ParserFactory())->createForNewestSupportedVersion()->parse($this->read($path)) ?? [];

        return (new NodeTraverser(new NameResolver()))->traverse($nodes);
    }

    private function directory(mixed $value, string $base, string $project): ?string
    {
        if (!\is_string($value)) {
            return null;
        }
        $value = str_replace('%kernel.project_dir%', $project, $value);
        if (preg_match('/[%*?{}!]/', $value)) {
            return null;
        }
        $path = str_starts_with($value, '/') ? $value : $base.'/'.$value;
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ('..' === $part) {
                array_pop($parts);
            } elseif ('' !== $part && '.' !== $part) {
                $parts[] = $part;
            }
        }
        $path = '/'.implode('/', $parts);

        return false !== ($real = realpath($path)) ? $real : $path;
    }
}
