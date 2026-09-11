<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Management

`Nexum\Management` bietet eine ausschließlich lesende Sicht auf die kompilierte Architektur. Es ersetzt das frühere Runtime-Enable/Disable-Modell.

## Aktivierung

```yaml
nexum:
    management:
        enabled: true
        api:
            enabled: false
            projects: []
```

Die CLI-Ansicht wird mit der Fähigkeit registriert. Die optionale GET-API benötigt zusätzlich aktivierte API- und Security-Integration sowie im Project Mode eine explizite Project-Allowlist.

## CLI

```bash
php bin/console nexum:architecture
```

Die Ausgabe zeigt Projects, Modules, Shared/Private Scope, Dependencies und aktive Zuordnung.

## HTTP

`ModuleController` liest `ModuleRegistryInterface` und liefert Architekturmetadaten. Es existieren keine POST-, PATCH-, DELETE-, Enable-, Disable- oder Remove-Endpunkte.

## Warum read-only?

Container, Router, Doctrine-Mappings, Subscriber und Handler werden beim Build kompiliert. Eine YAML-Datei zur Laufzeit umzuschreiben würde diesen Zustand nicht sicher aktualisieren und wäre konzeptionell falsch.

## Grenzen

- Keine Source-Änderung.
- Keine Runtime-Konfigurationsmutation.
- Keine Deployment-Steuerung.
- Keine automatische öffentliche Route.

Weiter: [CLI-Referenz](../reference/cli.md) und [Architektur](../architecture/overview.md).
