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
