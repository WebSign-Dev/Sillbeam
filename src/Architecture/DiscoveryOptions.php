<?php

declare(strict_types=1);

namespace Sillbeam\Architecture;

final readonly class DiscoveryOptions
{
    /** @var list<DiscoveryRoot> */
    public array $moduleRoots;
    /** @var list<DiscoveryRoot> */
    public array $projectRoots;

    /**
     * @param list<DiscoveryRoot>|null $moduleRoots
     * @param list<DiscoveryRoot>|null $projectRoots
     * @param list<string>             $excludes
     * @param list<string>             $controllerPatterns
     * @param list<string>             $entityPatterns
     * @param list<string>             $serviceExcludes
     */
    public function __construct(
        public string $root,
        public string $namespace,
        ?array $moduleRoots = null,
        ?array $projectRoots = null,
        public array $excludes = [],
        public array $controllerPatterns = ['Controller', '**/Controller'],
        public array $entityPatterns = ['Entity', '**/Entity'],
        public array $serviceExcludes = ['Entity/**', '**/Entity/**', 'Dto/**', '**/Dto/**'],
    ) {
        $this->moduleRoots = $moduleRoots ?? [new DiscoveryRoot($root.'/Module', $namespace.'Module\\')];
        $this->projectRoots = $projectRoots ?? [new DiscoveryRoot($root.'/Project', $namespace.'Project\\')];
        $namespaces = [];
        foreach ($this->roots() as $discoveryRoot) {
            if (isset($namespaces[$discoveryRoot->path]) && $namespaces[$discoveryRoot->path] !== $discoveryRoot->namespace) {
                throw new \LogicException('Conflicting discovery namespaces for '.$discoveryRoot->path);
            }
            $namespaces[$discoveryRoot->path] = $discoveryRoot->namespace;
        }
    }

    /** @return list<DiscoveryRoot> */
    public function roots(): array
    {
        return [new DiscoveryRoot($this->root, $this->namespace), ...$this->moduleRoots, ...$this->projectRoots];
    }

    public function excluded(string $file): bool
    {
        if (PathMatcher::matches($file, $this->excludes) || (str_starts_with($file, $this->root.'/') && PathMatcher::matches(substr($file, \strlen($this->root) + 1), $this->excludes))) {
            return true;
        }
        foreach ([...$this->moduleRoots, ...$this->projectRoots] as $root) {
            if ($root->contains($file) && PathMatcher::matches(substr($file, \strlen($root->path) + 1), $root->excludes)) {
                return true;
            }
        }

        return false;
    }

    public function namespaceForDirectory(string $directory): string
    {
        $roots = $this->roots();
        usort($roots, static fn (DiscoveryRoot $a, DiscoveryRoot $b): int => \strlen($b->path) <=> \strlen($a->path));
        foreach ($roots as $root) {
            if ($root->contains($directory)) {
                $relative = trim(substr($directory, \strlen($root->path)), '/');

                return rtrim($root->namespace.('' === $relative ? '' : str_replace('/', '\\', $relative)), '\\');
            }
        }
        throw new \LogicException('Directory has no configured discovery namespace: '.$directory);
    }
}
