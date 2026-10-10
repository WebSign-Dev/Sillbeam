# Konfiguration

Standard: `sillbeam: {}`. Die Source-Grenze ist `%kernel.project_dir%/src`, ihr Namespace wird aus Composer-PSR-4 bestimmt. Relative Pfade beziehen sich auf das Anwendungsverzeichnis. Mehrdeutige PSR-4-Zuordnungen brauchen einen expliziten Namespace.

```yaml
sillbeam:
    discovery:
        root: '%kernel.project_dir%/src'
        namespace: 'App\'
        excludes: ['Generated/**', '**/Tests/**']
        # Bei Angabe ersetzt jede Liste den Standard für diese Art.
        modules:
            - path: src/Domain
              namespace: 'App\Domain\'
              excludes: ['Legacy/**']
            - path: packages/Accounting/src
              namespace: 'Accounting\'
        projects:
            - path: src/Surface
              namespace: 'App\Surface\'
    integrations:
        controllers: [Http, '**/Controller']
        entities: [Model, '**/Entity']
        services:
            exclude: ['Model/**', '**/Entity/**', '**/Dto/**']
    modules:
        legacy: { enabled: false }
        'project:shop:old-reports': { enabled: false }
    projects:
        shop: { hosts: [shop.example.com, www.example.com] }
        admin: { hosts: [admin.example.com] }
    doctrine:
        enabled: null
```

Mehrere Konfigurationsdateien werden nach Symfony-Regeln zusammengeführt: Root-, Ausschluss- und Integrationslisten werden angehängt; ein expliziter Eintrag ersetzt den jeweiligen Standard. Eine leere Liste entfernt den Standard, löscht aber keine Einträge aus vorherigen Konfigurationsdateien.

Ohne angegebene Listen sind Module-Roots `src/Module` und Project-Roots `src/Project`. Eine leere Liste entfernt den jeweiligen Standard. Explizite Roots müssen existieren und Composer-Autoloading muss ihre Namespaces laden können. Gleiche Pfade mit widersprüchlichen Namespaces sind Fehler. Der Source-Root bleibt zusätzlich überwacht; Descriptoren außerhalb erlaubter Grenzen werden gemeldet.

Patterns verwenden `/`, `*` (ein Segment), `**` (rekursiv) und `?`. Globale Ausschlüsse sind relativ zum Source-Root oder absolut; lokale Ausschlüsse relativ zum jeweiligen Root. Ausschlüsse gelten für Discovery und automatische Service-, Route- und Entity-Imports. Controller-/Entity-Patterns wählen Verzeichnisse relativ zum Descriptorbereich. Ohne Angabe: `Controller`, `**/Controller` und `Entity`, `**/Entity`.

Service-Ausschlüsse: `Entity/**`, `**/Entity/**`, `Dto/**`, `**/Dto/**`, relativ zum Descriptor. Wer Entities unter `Model` ablegt, ergänzt dessen Service-Ausschluss. Symfony-native Attribute und explizite `services.yaml` bleiben verfügbar.

Doctrine: `null` aktiviert automatisch bei registriertem DoctrineBundle; `true` fordert es an und scheitert ohne Bundle; `false` deaktiviert Sillbeam-Mappings. Kurzform `doctrine: false` ist erlaubt. Nur aktive Module werden automatisch gemappt; direkte Project-Entities werden zusätzlich gemappt. Native manuelle Mappings bleiben erhalten, auch wenn sie bewusst ausgeschlossene Bereiche wieder aufnehmen.

Aktivierungsoptionen können `%env(bool:...)%` verwenden. Sie werden beim Build aufgelöst. Nach Änderungen von Build-Umgebungsvariablen den Containercache neu bauen.

Frühere Foundation-Keys wie `api`, `security`, `user` oder `management` sind ungültig; siehe [Migration](../migration/modular-monolith.md).

Project-Hosts sind exakte, getrimmte Hostnamen ohne Schema, Port, Pfad oder Wildcard; Groß-/Kleinschreibung wird normalisiert. IDs folgen `[a-z][a-z0-9-]*`. Unbekannte Modul-/Project-Konfigurationsadressen sind Buildfehler.
