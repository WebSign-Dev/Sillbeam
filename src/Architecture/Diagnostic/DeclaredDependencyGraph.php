<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

use Sillbeam\Core\Module\ModuleDefinition;

/** @internal Queries declarations only; no observed references or activation edges. */
final readonly class DeclaredDependencyGraph
{
    /** @var array<class-string, list<class-string>> */
    private array $edges;

    /** @param array<class-string, ModuleDefinition> $modules */
    public function __construct(private array $modules)
    {
        $edges = [];
        foreach ($modules as $class => $module) {
            $targets = array_values(array_unique($module->dependencies));
            sort($targets, \SORT_STRING);
            $edges[$class] = $targets;
        }
        ksort($edges, \SORT_STRING);
        $this->edges = $edges;
    }

    /** Shortest directed witness, with lexical tie breaking. Null means no witnessed path.
     * @return list<class-string>|null
     */
    public function path(string $source, string $target): ?array
    {
        if (!isset($this->modules[$source], $this->modules[$target])) {
            return null;
        }
        $queue = new \SplQueue();
        $queue->enqueue($source);
        $parents = [$source => null];
        while (!$queue->isEmpty()) {
            $class = $queue->dequeue();
            if ($class === $target) {
                $path = [];
                for ($node = $target; null !== $node; $node = $parents[$node]) {
                    $path[] = $node;
                }

                return array_reverse($path);
            }
            foreach ($this->edges[$class] as $dependency) {
                if (isset($this->modules[$dependency]) && !\array_key_exists($dependency, $parents)) {
                    $parents[$dependency] = $class;
                    $queue->enqueue($dependency);
                }
            }
        }

        return null;
    }

    /** Whether the reachable declaration subgraph contains a missing descriptor. */
    public function hasUnresolvedTargets(string $source): bool
    {
        $pending = [$source];
        $visited = [];
        while ([] !== $pending) {
            $class = array_pop($pending);
            if (!isset($this->edges[$class])) {
                return true;
            }
            if (isset($visited[$class])) {
                continue;
            }
            $visited[$class] = true;
            foreach ($this->edges[$class] as $target) {
                $pending[] = $target;
            }
        }

        return false;
    }

    /** Deterministic DFS back-edge witnesses; not an enumeration of every elementary cycle.
     * @return list<list<class-string>>
     */
    public function cycles(): array
    {
        $visited = [];
        $visiting = [];
        $stack = [];
        $cycles = [];
        $visit = function (string $class) use (&$visit, &$visited, &$visiting, &$stack, &$cycles): void {
            if (isset($visiting[$class])) {
                $cycles[] = [...\array_slice($stack, $visiting[$class]), $this->modules[$class]->class];

                return;
            }
            if (isset($visited[$class]) || !isset($this->edges[$class])) {
                return;
            }
            $visiting[$class] = \count($stack);
            $stack[] = $this->modules[$class]->class;
            foreach ($this->edges[$class] as $dependency) {
                $visit($dependency);
            }
            array_pop($stack);
            unset($visiting[$class]);
            $visited[$class] = true;
        };
        foreach (array_keys($this->edges) as $class) {
            $visit($class);
        }

        return $cycles;
    }

    /** Every listed file proves the outgoing edge at that path position; no invented lines.
     * @param list<class-string> $path
     *
     * @return array<class-string, string>
     */
    public function sourceDescriptors(array $path): array
    {
        $files = [];
        for ($index = 0; $index < \count($path) - 1; ++$index) {
            $source = $path[$index];
            if (!isset($this->modules[$source], $this->modules[$path[$index + 1]]) || !\in_array($path[$index + 1], $this->edges[$source], true)) {
                throw new \InvalidArgumentException('Path contains an unproven declared edge.');
            }
            $files[$source] = $this->modules[$source]->descriptorFile;
        }

        return $files;
    }
}
