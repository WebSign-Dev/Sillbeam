# CLI

```bash
php bin/console sillbeam:about
php bin/console sillbeam:architecture
php bin/console sillbeam:architecture --format=json
php bin/console debug:container
php bin/console debug:router
php bin/console doctrine:mapping:info  # bei installiertem DoctrineBundle
```

`sillbeam:architecture` zeigt den gesamten Katalog einschließlich deaktivierter und nicht zugeordneter Module. JSON enthält Scope, Eigentümer, enabled/active, inactive_reason (`disabled`/`unassigned`), Pfade, direkte Deklarationen, beobachtete Dependencies, Datei-/Zeilenbelege und Aktivierungsgründe. Projects enthalten Hosts und ihren kompilierten Graphen. Unbekannte Ausgabeformate liefern Exitcode 2.

Architekturfehler verhindern bereits den Container-Build, bevor die CLI laufen kann. Ihre Exceptions enthalten beteiligte Module und bei tatsächlichen Codereferenzen Datei/Zeile. Symfony-Diagnose bleibt parallel nutzbar.
