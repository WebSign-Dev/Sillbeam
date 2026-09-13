<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Symfony Flex Recipe

Die Nexum-Recipe reduziert die Installation auf einen Composer-Befehl und hält dabei die Sicherheitsgrenzen des Frameworks ein. Sie ist als versionierte Symfony-Recipes-Contrib-Quelle im Repository enthalten und wird mit einem realen Flex-Installationslauf geprüft.

## Was Flex für Nexum übernimmt

Nach:

```bash
composer require benjaminoeffinger/nexum
```

führt die Recipe genau diese Änderungen in der Verbraucher-Anwendung aus:

| Ziel | Änderung |
| --- | --- |
| `config/bundles.php` | registriert ausschließlich `Nexum\NexumBundle` für `all` |
| `config/packages/nexum.yaml` | legt die sichere Nexum-Grundkonfiguration an |
| `config/routes/nexum.yaml` | registriert einmalig den Route Loader `type: nexum` |
| `symfony.lock` | speichert Paket-, Recipe-Version, Herkunft und erzeugte Dateien |

Die Recipe legt keine Modules oder Projects an. Diese entstehen ausschließlich aus dem Anwendungscode und werden anschließend durch Nexums Descriptor-Discovery gefunden.

## Sichere Grundkonfiguration

Die installierte Datei enthält bewusst nur:

```yaml
nexum:
    doctrine: { enabled: false }
    api: { enabled: false }
    security: { enabled: false }
```

Doctrine, API und Security sind in der Framework-Konfiguration ohne explizite Angaben grundsätzlich aktiviert. Die Recipe schaltet sie für eine neue Anwendung aus, damit Installation und Exposition getrennte Entscheidungen bleiben. User, Filesystem, Mailer, Messenger, QR-Code, MFA, Passkeys und Management sind bereits durch ihre jeweiligen Konfigurationsknoten standardmäßig deaktiviert.

Damit gilt unmittelbar nach der Installation:

- keine Token-, MFA-, Passkey- oder Management-Route ist öffentlich;
- Nexum setzt keine Datenbankverbindung voraus;
- reine Klassen und der Module-/Project-Architekturlayer bleiben nutzbar;
- Integrationen werden später einzeln und nachvollziehbar eingeschaltet.

Beispiel für die bewusste Aktivierung von Doctrine und API:

```yaml
nexum:
    doctrine: { enabled: true }
    api: { enabled: true }
    security: { enabled: false }
```

Die vollständigen Abhängigkeiten und Schalter stehen in der [Konfigurationsreferenz](../reference/configuration.md). Sicherheitsrelevante APIs werden zusätzlich nach der [Security-Anleitung](security.md) freigeschaltet.

## Warum die Recipe außerhalb des Packages liegt

Symfony Flex bezieht Recipes aus einem Recipe-Repository. Eine Library kann ihre eigene Recipe nicht allein dadurch aktivieren, dass Dateien im Composer-Package liegen. Deshalb sind Package und Recipe zwei getrennte Veröffentlichungsartefakte:

```text
benjaminoeffinger/nexum
└── PHP-Code auf Packagist

symfony/recipes-contrib
└── benjaminoeffinger/nexum/0.1
    ├── manifest.json
    └── config/
```

Die Nexum-Quelle dafür liegt unter:

```text
flex-recipe/benjaminoeffinger/nexum/0.1/
├── manifest.json
└── config/
    ├── packages/nexum.yaml
    └── routes/nexum.yaml
```

Diese Struktur ist direkt für einen Pull Request gegen Symfony Recipes Contrib vorbereitet. Änderungen werden zuerst hier vorgenommen und getestet; die veröffentlichte Recipe wird daraus synchronisiert.

## Zustimmung zu Contrib-Recipes

Recipes Contrib enthält Recipes von Drittanbietern. Flex fragt deshalb normalerweise nach einer bewussten Zustimmung. Für lokale oder interaktive Installationen kann diese Frage beim `composer require` einzeln bestätigt werden.

Das Nexum-Skeleton erlaubt Contrib-Recipes ausdrücklich:

```json
{
    "extra": {
        "symfony": {
            "allow-contrib": true
        }
    }
}
```

Dieser globale Schalter vertraut nicht nur Nexum, sondern allen installierten Contrib-Recipes. Teams sollten diese Entscheidung in ihrer Root-Anwendung bewusst treffen und Recipe-Änderungen wie andere Build-Konfiguration prüfen.

## Nutzung vor der Veröffentlichung

Bis die Recipe in Recipes Contrib verfügbar ist, werden die drei Zieländerungen manuell übernommen:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Nexum\NexumBundle::class => ['all' => true],
];
```

```yaml
# config/packages/nexum.yaml
nexum:
    doctrine: { enabled: false }
    api: { enabled: false }
    security: { enabled: false }
```

```yaml
# config/routes/nexum.yaml
nexum:
    resource: .
    type: nexum
```

Ein eigenes Flex-Repository kann dieselbe versionierte Quelle schon vor dem Contrib-Merge bereitstellen. Der End-to-End-Test verwendet genau diesen Weg lokal, ohne die Anwendungsdateien im Repository zu verändern.

## Recipe prüfen und aktualisieren

Status der installierten Recipe:

```bash
composer recipes benjaminoeffinger/nexum
```

Aktualisierbare Recipes anzeigen:

```bash
composer recipes:update
```

Recipe bewusst neu installieren:

```bash
composer recipes:install benjaminoeffinger/nexum --force -v
```

`--force` kann lokale Konfigurationsdateien ersetzen. Vorher sollten Änderungen versioniert und der angezeigte Diff geprüft werden. Eine Recipe-Aktualisierung aktiviert keine Nexum-Integration automatisch; sicherheitsrelevante Schalter bleiben Anwendungskonfiguration.

## Tests im Nexum-Repository

Schnelle Struktur- und Verhaltenstests laufen mit PHPUnit:

```bash
composer test
```

Der vollständige Installationsweg läuft separat:

```bash
composer test:flex-recipe
```

Dieser Test:

1. liest Manifest und Konfigurationsdateien aus der versionierten Contrib-Struktur;
2. baut daraus einen lokalen Flex-Endpunkt;
3. installiert das aktuelle Nexum-Package in eine temporäre Composer-Anwendung;
4. lässt Symfony Flex die Recipe wirklich anwenden;
5. prüft Bundle, sichere Defaults, Routing und `symfony.lock`;
6. entfernt die temporäre Anwendung wieder.

Für den End-to-End-Lauf benötigt Composer Zugriff auf seine Paketquellen beziehungsweise einen vollständig gefüllten Cache. Die Recipe selbst wird ausschließlich über den lokalen Test-Endpunkt geladen.

## Fehlerdiagnose

### Flex meldet keine Nexum-Recipe

Prüfen:

```bash
composer recipes benjaminoeffinger/nexum
composer show symfony/flex benjaminoeffinger/nexum
```

Vor dem Merge in Recipes Contrib ist das erwartetes Verhalten; dann gelten die manuellen Einträge. Nach Veröffentlichung sind eine abgelehnte Contrib-Zustimmung, ein veralteter Flex-Index oder eine bereits installierte Paketversion typische Ursachen.

### Bundle ist registriert, YAML-Dateien fehlen

Flex kann Bundles auch durch eine automatisch erzeugte Fallback-Recipe erkennen. Das beweist noch nicht, dass die Nexum-Recipe angewendet wurde. In `symfony.lock` muss für `benjaminoeffinger/nexum` ein `recipe`-Block mit Version und Herkunft vorhanden sein.

### Bestehende Datei wurde nicht überschrieben

Das ist normales Flex-Verhalten und schützt lokale Änderungen. Inhalt manuell zusammenführen oder nach gesichertem Diff gezielt `composer recipes:install benjaminoeffinger/nexum --force -v` verwenden.

Weiterführend: [Erste Schritte](getting-started.md), [Konfiguration](../reference/configuration.md), [Migration](../migration/modular-monolith.md) und [Qualität & Tests](../development/testing.md).
