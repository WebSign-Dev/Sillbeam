<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Routing

`Nexum\Routing\NexumRouteLoader` verbindet die kompilierte Architektur mit Symfonys Router.

## Einmaliger Import

```yaml
nexum:
    resource: .
    type: nexum
```

Der Loader kann genau einmal geladen werden. Normale `src/Controller` sind nicht seine Aufgabe und bleiben beim Symfony-Standardimport.

## Application Mode

Controller aller aktiven Shared Modules werden importiert. Routennamen bleiben unverändert und erhalten keine Nexum-Hostbedingung.

## Project Mode

Für jedes Project importiert der Loader:

- Project-eigene Controller;
- Controller zugeordneter Shared Modules;
- Controller project-privater Modules;
- bewusst für dieses Project freigeschaltete Nexum-APIs.

Routennamen erhalten `<project-id>.`. Bei konfigurierten Hosts setzt der Loader den reservierten Parameter `_nexum_host`, alle erlaubten Hosts als Requirement und den Canonical Host als Default. Dadurch funktionieren Aliase inbound und absolute URL-Generierung deterministisch.

## Cache

Controllerverzeichnisse werden als `DirectoryResource`, Descriptoren und Konfigurationsdateien als `FileResource` an die RouteCollection gehängt. Neue, geänderte und entfernte Controller invalidieren damit den Routing-Cache.

Weiter: [Projects](../guides/projects.md) und [Fehlerdiagnose](../operations/troubleshooting.md).
