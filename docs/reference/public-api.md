# Öffentliche API

- `NexumBundle`: zentraler Symfony-Einstieg.
- `ModuleAttributeNexumModule`: `id`, `description`, `dependencies`.
- `ProjectAttributeNexumProject`: `id`, `description`, `modules`.
- `ModuleRegistryInterface`: `all()`, `get(class)`, `active()`, `forApplication()`, `forProject(class)`.
- `ProjectRegistryInterface`: `all()`, `get(idOrClass)` und `forHost(host)`.
- `ProjectContextInterface`: `current()`, `id()`, `is(id)`, `hasModule(class)`.
- `ModuleDefinition`/`ProjectDefinition`: unveränderliche Katalog- und Aktivierungsdaten. Module enthalten deklarierte/erkannte Dependencies, Referenzbelege und Aktivierungsgründe.
- `KernelNexumKernel`: optionaler Kernel; gewöhnlicher Symfony-Kernel bleibt unterstützt.
- `KernelInformationInterface`: Laufzeitdaten für `nexum:about`.

Discovery, Compiler, AST-Index und native Loader sind Implementierungsbausteine des Builds. Foundation-Verträge gehören seit der Evolution nicht mehr zur öffentlichen API. Der [Migrationsleitfaden](../migration/modular-monolith.md) nennt entfernte Bereiche.
