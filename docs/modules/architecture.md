<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Architecture

`Nexum\Architecture` implementiert die compile-time Pipeline zwischen Dateisystem und Symfony-Container.

## Bausteine

| Klasse | Verantwortung |
| --- | --- |
| `DiscoveryOptionsResolver` | Discovery-Root und PSR-4-Namespace bestimmen |
| `ArchitectureDiscovery` | Module- und Project-Discovery koordinieren |
| `SourceInspector` | Class-Strings aus PSR-4-Dateien bestimmen und reflektieren |
| `IdDeriver` | `BillingModule` → `billing`, `AdminProject` → `admin` |
| `DiscoveryResult` | gefundene Definitionen und Cache-Ressourcen transportieren |
| `ArchitectureCompiler` | Overrides anwenden, Graphen validieren und Aktivität berechnen |
| `CompiledArchitecture` | unveränderliches Ergebnis für Services, Doctrine und Routing |

## Pipeline

```text
composer.json + src/
        ↓
DiscoveryOptions
        ↓
ModuleDiscovery + ProjectDiscovery
        ↓
DiscoveryResult + nexum.yaml
        ↓
ArchitectureCompiler
        ↓
CompiledArchitecture
```

Discovery arbeitet unabhängig von Symfony-Autoconfiguration. Descriptoren müssen daher nicht vorab als Services geladen werden. Erst die kompilierte Architektur bestimmt, welche Anwendungsbereiche als Services registriert werden.

## Validierung

Strukturelle Fehler – unbekannte Dependencies, Zyklen, doppelte IDs und Scope-Verstöße – sind immer Build-Fehler. Ein deaktiviertes Dependency wird erst dann zum Fehler, wenn ein tatsächlich aktiver Application- oder Project-Graph es benötigt.

## Cache-Ressourcen

Dateien und Verzeichnisse aus Discovery werden als Container-Ressourcen registriert. Routing verwaltet zusätzlich eigene Ressourcen, weil Container- und Router-Cache getrennte Lebenszyklen besitzen.

Diese Klassen sind primär Framework-Interna. Anwendungen integrieren über Attribute und Registries, nicht durch direkten Aufruf des Compilers.

Weiter: [Architekturübersicht](../architecture/overview.md), [Module-System](module-system.md) und [Project-System](project-system.md).
