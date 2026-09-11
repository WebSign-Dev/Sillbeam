<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Architektur

## Zielbild

Nexum ist ein modularer Monolith in einem Composer-Package und einem Symfony-Bundle. Die internen Namespaces bilden fachliche und technische Grenzen, aber keine eigenständig deploybaren Microservices.

```text
benjaminoeffinger/nexum
└── Nexum\
    ├── Core
    ├── Entity
    ├── Doctrine
    ├── Api
    ├── Security
    ├── User
    ├── Module
    ├── Project
    ├── Routing
    └── ...
```

## Begriffe

| Begriff | Bedeutung in Nexum |
| --- | --- |
| Package | Veröffentlichungs- und Installationsgrenze in Composer |
| Bundle | Einziger Symfony-Integrationspunkt: `NexumBundle` |
| Komponente | Interner Namespace-Bereich wie `Nexum\Entity` |
| Module | Fachliche Struktur innerhalb einer Verbraucheranwendung |
| Project | Hostbasierter Anwendungsbereich mit eigener Routenoberfläche |
| Microservice | Eigenständig laufender und deploybarer Dienst; nicht das Nexum-Modell |

## Build-Pipeline

```text
DISCOVER
   ↓
CONFIGURE
   ↓
VALIDATE
   ↓
COMPILE
```

### Discover

Während des Container-Builds werden Descriptor-Dateien über den Discovery-Root und das passendste PSR-4-Mapping gefunden. `ReflectionClass` wertet `#[NexumModule]` und `#[NexumProject]` aus. Symfony-Autoconfiguration ist nicht für die Descriptor-Suche zuständig.

### Configure

Die zentrale `nexum:`-Konfiguration ergänzt Discovery-Ergebnisse um deaktivierte Module, Project-Hosts, Integrationseinstellungen und API-Allowlisten.

### Validate

Der Architekturgraph stoppt den Build unter anderem bei fehlenden Abhängigkeiten, Zyklen, doppelten IDs, Shared→Private-Abhängigkeiten, projectübergreifenden privaten Abhängigkeiten und widersprüchlichen Hosts.

### Compile

Der validierte Graph wird in unveränderliche `ModuleDefinition`-, `ProjectDefinition`- und Registry-Services übersetzt. Im Request werden keine YAML-Dateien erneut gelesen und Registries nicht mutiert.

## Discovery und Dependency Injection

Descriptoren beschreiben Architektur und bleiben normale PHP-Metadatenklassen. Erst nach der Discovery lädt Nexum die verwendeten Modul- und Project-Verzeichnisse in den Symfony-Container. Dabei gelten:

- `autowire: true`
- `autoconfigure: true`
- `Entity/` und `Dto/` werden nicht als Services geladen
- Descriptor-Dateien werden nicht als Services geladen
- verschachtelte `Project/*/Module/`-Bäume werden separat behandelt

Doctrine-Mappings, Container-Ressourcen und Router-Ressourcen werden getrennt registriert. Änderungen an Descriptoren oder Controllern invalidieren damit die jeweils zuständigen Caches.

## Aktivität ist keine Isolation

Im Project Mode steuert die Module-Zuordnung:

- Project-Routen
- exponierte Nexum-APIs
- `ProjectContextInterface::hasModule()`
- Project-spezifische Controller und Ressourcen

Sie steuert nicht automatisch:

- die Existenz verwendeter Services im globalen Container
- Event Subscriber
- Messenger Handler
- Console Commands
- Datenbanktabellen oder Doctrine-Mappings
- Tokens oder Tenant-Daten

Ein projectgebundenes Modul wird containerweit kompiliert, sobald sein Project aktiv ist. Wer Laufzeitlogik projectspezifisch ausführen möchte, prüft dies ausdrücklich oder führt einen eigenen project-aware Adapter ein.

## Abhängigkeitsrichtung

`deptrac.yaml` hält technische Grenzen fest. Besonders wichtig: Core-, Entity- und reine Utility-Bereiche dürfen nicht von Module, Project oder Routing abhängen. Dadurch bleiben Value Objects und Basisklassen ohne Kernel verwendbar.

## Erweiterungspunkte

- eigene Implementierungen hinter Nexum-Interfaces über Symfony-Aliase
- normale Symfony-Service-Dekoration
- eigene Controller und Routen
- eigene Event Subscriber und Messenger Handler
- eigene Provider für Storage, Payment, Search und Notifications
- optionaler `NexumKernel` als Komfort, nicht als Voraussetzung

Die detaillierte Foundation-Entscheidung ist in [foundation.md](foundation.md) dokumentiert.
