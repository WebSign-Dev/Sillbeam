<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Dependency Injection

`Nexum\DependencyInjection` ist der einzige Symfony-Integrationsbereich des Einzelbundles.

## NexumExtension

`prepend()` löst Discovery-Optionen aus roher Konfiguration auf, führt strukturelle Discovery aus, registriert Cache-Ressourcen und bereitet Doctrine-Mappings beziehungsweise notwendige Framework-Konfiguration früh vor.

`load()` verarbeitet den vollständigen `nexum:`-Baum, kompiliert den Graph, validiert Integrationskombinationen, lädt Anwendungsservices und registriert Registries, Context sowie Route Loader.

## Configuration

Die TreeBuilder-Konfiguration definiert Defaults, Typen und Wertebereiche. Semantische Abhängigkeiten – etwa API benötigt Doctrine oder Passkeys benötigen passende Relying Parties – werden zusätzlich mit konkreten Build-Fehlern validiert.

## ArchitectureServiceLoader

Der Loader registriert konkrete Klassen verwendeter Modules und Projects mit Autowiring und Autoconfiguration. Ausgeschlossen werden Descriptoren, `Entity/`, `Dto/` und verschachtelte private Modulbäume, die separat verarbeitet werden.

## ArchitectureValidationPass

Der Compiler Pass stellt sicher, dass der Architekturgraph vor Abschluss des Container-Builds validiert wurde. Registry-Daten bleiben danach immutable.

Anwendungen sollten diese Klassen nicht direkt instanziieren. Erweiterungen erfolgen über normale Symfony-Aliase, Decoration und Konfiguration.

Weiter: [Konfigurationsreferenz](../reference/configuration.md), [Architecture](architecture.md) und [Architekturübersicht](../architecture/overview.md).
