# Public API

- `SillbeamBundle`: central Symfony entry point.
- `Module\Attribute\SillbeamModule`: `id`, `description`, `dependencies`.
- `Project\Attribute\SillbeamProject`: `id`, `description`, `modules`.
- `ModuleRegistryInterface`: `all()`, `get(class)`, `active()`, `forApplication()`, `forProject(class)`.
- `ProjectRegistryInterface`: `all()`, `get(idOrClass)` and `forHost(host)`.
- `ProjectContextInterface`: `current()`, `id()`, `is(id)`, `hasModule(class)`.
- `ModuleDefinition`/`ProjectDefinition`: immutable catalog and activation data. Modules include declared/observed dependencies, reference evidence and activation reasons.
- `Kernel\SillbeamKernel`: optional kernel; standard Symfony kernels remain supported.
- `KernelInformationInterface`: runtime information for `sillbeam:about`.

Discovery, compiler, AST index and native loaders are build implementation components. Foundation contracts are no longer part of the public API since the evolution. The [migration guide](../migration/modular-monolith.md) lists removed areas.

Registry lookups for unknown classes, IDs or hosts throw `OutOfBoundsException`. The optional request context distinguishes missing Projects/requests (`null`) from unassigned hosts when Projects exist (exception); see [Projects](../guides/projects.md).

## Types and return values

The full namespaces of the registry contracts are `Sillbeam\Module\ModuleRegistryInterface`, `Sillbeam\Project\ProjectRegistryInterface` and `Sillbeam\Project\ProjectContextInterface`. They can be injected directly through constructors.

Registry lists are arrays keyed by descriptor class names with `ModuleDefinition` or `ProjectDefinition` values. `ModuleRegistryInterface::get()` and `forProject()` accept descriptor class names, not IDs; `ProjectRegistryInterface::get()` accepts an ID or class. `active()` is the union of active graphs. `forApplication()` is empty in Project mode.

`ModuleDefinition` provides, among other fields, `id`, `class`, `description`, `dependencies`, `enabled`, `projectClass`, directories/namespace, `observedDependencies`, `dependencyReferences` and `activationReasons`. `enabled` does not automatically mean active; activity is determined by registry membership, with no `active` property. `isPrivate()` checks Project ownership; `key($projectId)` requires the owning Project ID for private modules.

`ProjectDefinition` contains `declaredModules`, the compiled `modules` graph, `hosts` and discovery data. `canonicalHost(): ?string` returns the first host or `null`. `Sillbeam\Kernel\Runtime\KernelInformationInterface::all()` returns an `array<string, string|int>`.

```php
use Sillbeam\Module\ModuleRegistryInterface;

final readonly class ArchitectureStatus
{
    public function __construct(private ModuleRegistryInterface $modules) {}

    public function activeModuleCount(): int
    {
        return count($this->modules->active());
    }
}
```
