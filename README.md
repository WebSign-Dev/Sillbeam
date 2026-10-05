<p align="center"><img src="docs/assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Nexum

Nexum ergänzt Symfony um eine konfigurierbare Modularchitektur. Modules organisieren Code und deklarieren direkte Abhängigkeiten. Optionale Projects bündeln Oberflächen in derselben Anwendung. Discovery, Graphprüfung und statische Codeprüfung laufen beim Container-Build; Symfony bleibt für Services, Routing und Laufzeit verantwortlich.

PHP 8.4+, Symfony 7.4 oder 8.x. Doctrine ist optional. Es gibt einen gemeinsamen Container und keine Datenbank-, Prozess- oder Mandantenisolation pro Project. Der normale Symfony-Kernel genügt; `NexumKernel` ist eine Komfortoption.

## Einstieg

Das Package heißt `benjaminoeffinger/nexum`. Bis zum ersten stabilen Release ist eine explizite Development-Version beziehungsweise ein lokales Composer-Path-Repository nötig. Die vorbereitete [Flex-Recipe](flex-recipe/README.md) wird extern installiert; ihre Veröffentlichung ist unabhängig vom PHP-Package.

Ohne Recipe drei Einträge anlegen:

```php
// config/bundles.php
Nexum\NexumBundle::class => ['all' => true],
```

```yaml
# config/packages/nexum.yaml
nexum: {}
```

```yaml
# config/routes/nexum.yaml
nexum:
    resource: .
    type: nexum
```

```php
// src/Module/Billing/BillingModule.php
namespace App\Module\Billing;

use Nexum\Module\Attribute\NexumModule;

#[NexumModule]
final class BillingModule {}
```

Nexum leitet `billing` und den PSR-4-Namespace ab. Der Descriptor besitzt sein Verzeichnis. Services werden mit Symfony-Autowiring/Autoconfiguration registriert, Routen aus `Controller` importiert und bei vorhandenem DoctrineBundle Entities aus `Entity` gemappt. `Entity` und `Dto` sind standardmäßig vom Serviceimport ausgeschlossen. Descriptor-Dateinamen sind frei, müssen aber ihrem PSR-4-Klassennamen entsprechen.

`src/Module` und `src/Project` sind Standards. Mehrere Roots, Namespaces, Ausschlüsse und alternative Controller-/Entity-Verzeichnisse sind [konfigurierbar](docs/reference/configuration.md). Ohne Projects werden alle aktivierten Shared Modules verwendet. Mit Projects aktivieren deren Deklarationen und private Module den jeweiligen Graphen. Nicht zugeordnete Shared Modules bleiben im Katalog, tragen aber keine automatischen Services, Routen oder Mappings bei.

```bash
php bin/console nexum:architecture
php bin/console nexum:architecture --format=json
php bin/console nexum:about
```

Die Architekturprüfung erkennt direkte statische Klassenreferenzen zwischen Modulen und meldet unerlaubte Zugriffe mit Datei und Zeile. Dynamische Klassennamen und PHPDoc gehören nicht zu dieser Prüfung. Unbenutzte Imports erzeugen keine Abhängigkeiten.

## Migration und Entwicklung

Die frühere Foundation mit API, Security, User, Entity-Basen, Stores und Infrastrukturhilfen wurde entfernt. Die Umstellung ist eine **Breaking Change**; Anwendungen müssen ihre Nutzung vor einem Update gemäß [Migration](docs/migration/modular-monolith.md) ersetzen. Strata bleibt eine Zukunftsidee und ist keine Abhängigkeit.

```bash
composer install
composer test
composer analyse
composer architecture
composer cs:check
composer validate --strict
composer test:flex-recipe
```

Die [Test-Apps](docs/development/test-apps.md) decken sechs Betriebsformen ab. Die Harbor-Verbraucher-App liegt unter `apps/test-app`; ihre lokale Laufzeit wird über `fabryq harbor` in diesem Verzeichnis verwaltet.

Das Handbuch beginnt unter [docs/README.md](docs/README.md).
