<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# CLI-Referenz

Nexum-Kommandos sind Diagnose- und Arbeitswerkzeuge. Sie verändern keine Modul- oder Project-Konfiguration zur Laufzeit.

## `nexum:about`

Zeigt die wichtigsten Laufzeitinformationen:

```bash
php bin/console nexum:about
```

Enthalten sind Anwendung, Environment, Debug-Modus, PHP-, Symfony- und Nexum-Version sowie Modulzahlen.

## `nexum:architecture`

Zeigt den kompilierten Architekturstand:

```bash
php bin/console nexum:architecture
```

Die Ausgabe umfasst gefundene Modules und Projects, IDs, Scopes, Dependencies und aktive Zuordnungen. Sie basiert auf den immutable Registries und schreibt keine Dateien.

## `nexum:user:create`

Wenn die User-Integration aktiv ist, kann ein Benutzer interaktiv angelegt werden:

```bash
php bin/console nexum:user:create user@example.com \
  --role=ROLE_USER \
  --permission=billing.invoice.read
```

Das Passwort wird sicher abgefragt und über den konfigurierten Symfony-PasswordHasher verarbeitet.

## Symfony-Diagnose

Nexum bleibt vollständig in den Symfony-Werkzeugen sichtbar:

```bash
php bin/console debug:router
php bin/console debug:container Nexum\\Module\\ModuleRegistryInterface
php bin/console debug:container Nexum\\Project\\ProjectContextInterface
php bin/console config:dump-reference nexum
```

Es gibt bewusst keinen mutierenden `nexum:module enable|disable|remove`-Befehl. Aktivierung ist Build-/Deployment-Konfiguration.
