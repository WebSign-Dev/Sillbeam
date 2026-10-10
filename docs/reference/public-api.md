# Öffentliche API

- `SillbeamBundle`: zentraler Symfony-Einstieg.
- `Module\Attribute\SillbeamModule`: `id`, `description`, `dependencies`.
- `Project\Attribute\SillbeamProject`: `id`, `description`, `modules`.
- `ModuleRegistryInterface`: `all()`, `get(class)`, `active()`, `forApplication()`, `forProject(class)`.
- `ProjectRegistryInterface`: `all()`, `get(idOrClass)` und `forHost(host)`.
- `ProjectContextInterface`: `current()`, `id()`, `is(id)`, `hasModule(class)`.
- `ModuleDefinition`/`ProjectDefinition`: unveränderliche Katalog- und Aktivierungsdaten. Module enthalten deklarierte/erkannte Dependencies, Referenzbelege und Aktivierungsgründe.
- `Kernel\SillbeamKernel`: optionaler Kernel; gewöhnlicher Symfony-Kernel bleibt unterstützt.
- `KernelInformationInterface`: Laufzeitdaten für `sillbeam:about`.

Discovery, Compiler, AST-Index und native Loader sind Implementierungsbausteine des Builds. Foundation-Verträge gehören seit der Evolution nicht mehr zur öffentlichen API. Der [Migrationsleitfaden](../migration/modular-monolith.md) nennt entfernte Bereiche.

Registry-Lookups unbekannter Klassen, IDs oder Hosts werfen `OutOfBoundsException`. Der optionale Request-Kontext unterscheidet fehlende Projects/Requests (`null`) von nicht zugeordneten Hosts bei vorhandenen Projects (Exception); siehe [Projects](../guides/projects.md).

## Typen und Rückgaben

Die vollständigen Namespaces der Registry-Verträge sind `Sillbeam\Module\ModuleRegistryInterface`, `Sillbeam\Project\ProjectRegistryInterface` und `Sillbeam\Project\ProjectContextInterface`. Sie können direkt per Konstruktor injiziert werden.

Registry-Listen sind Arrays mit Descriptor-Klassennamen als Schlüssel und `ModuleDefinition` beziehungsweise `ProjectDefinition` als Werten. `ModuleRegistryInterface::get()` und `forProject()` nehmen Descriptor-Klassennamen, keine IDs; `ProjectRegistryInterface::get()` akzeptiert ID oder Klasse. `active()` ist die Vereinigung der aktiven Graphen. `forApplication()` ist im Project-Modus leer.

`ModuleDefinition` bietet unter anderem `id`, `class`, `description`, `dependencies`, `enabled`, `projectClass`, Verzeichnisse/Namespace, `observedDependencies`, `dependencyReferences` und `activationReasons`. `enabled` bedeutet nicht automatisch aktiv; Aktivität wird über Registry-Mitgliedschaft bestimmt, es gibt keine `active`-Property. `isPrivate()` prüft Project-Ownership; `key($projectId)` benötigt für private Module die zugehörige Project-ID.

`ProjectDefinition` enthält `declaredModules`, den kompilierten `modules`-Graphen, `hosts` und Discovery-Daten. `canonicalHost(): ?string` liefert den ersten Host oder `null`. `Sillbeam\Kernel\Runtime\KernelInformationInterface::all()` liefert ein `array<string, string|int>`.

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
