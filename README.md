<p align="center"><img src="docs/assets/sillbeam-logo.png" alt="Sillbeam" width="520"></p>

# Sillbeam

Sillbeam ergänzt Symfony um eine konfigurierbare Modularchitektur. Modules organisieren Code und deklarieren direkte Abhängigkeiten. Optionale Projects bündeln Oberflächen in derselben Anwendung. Discovery, Graphprüfung und statische Codeprüfung laufen beim Container-Build; Symfony bleibt für Services, Routing und Laufzeit verantwortlich.

PHP 8.4+, Symfony 7.4 oder 8.x. Doctrine ist optional. Es gibt einen gemeinsamen Container und keine Datenbank-, Prozess- oder Mandantenisolation pro Project. Der normale Symfony-Kernel genügt; `SillbeamKernel` ist eine Komfortoption.

## Einstieg

Das Package heißt `websign-dev/sillbeam`. Bis zum ersten stabilen Release ist eine explizite Development-Version beziehungsweise ein lokales Composer-Path-Repository nötig. Die vorbereitete [Flex-Recipe](flex-recipe/README.md) wird extern installiert; ihre Veröffentlichung ist unabhängig vom PHP-Package.

Ohne Recipe drei Einträge anlegen:

```php
// config/bundles.php
Sillbeam\SillbeamBundle::class => ['all' => true],
```

```yaml
# config/packages/sillbeam.yaml
sillbeam: {}
```

```yaml
# config/routes/sillbeam.yaml
sillbeam:
    resource: .
    type: sillbeam
```

```php
// src/Module/Billing/BillingModule.php
namespace App\Module\Billing;

use Sillbeam\Module\Attribute\SillbeamModule;

#[SillbeamModule]
final class BillingModule {}
```

Sillbeam leitet `billing` und den PSR-4-Namespace ab. Der Descriptor besitzt sein Verzeichnis. Services werden mit Symfony-Autowiring/Autoconfiguration registriert, Routen aus `Controller` importiert und bei vorhandenem DoctrineBundle Entities aus `Entity` gemappt. `Entity` und `Dto` sind standardmäßig vom Serviceimport ausgeschlossen. Descriptor-Dateinamen sind frei, müssen aber ihrem PSR-4-Klassennamen entsprechen.

`src/Module` und `src/Project` sind Standards. Mehrere Roots, Namespaces, Ausschlüsse und alternative Controller-/Entity-Verzeichnisse sind [konfigurierbar](docs/reference/configuration.md). Ohne Projects werden alle aktivierten Shared Modules verwendet. Mit Projects aktivieren deren Deklarationen und private Module den jeweiligen Graphen. Nicht zugeordnete Shared Modules bleiben im Katalog, tragen aber keine automatischen Services, Routen oder Mappings bei.

```bash
php bin/console sillbeam:architecture
php bin/console sillbeam:architecture --format=json
php bin/console sillbeam:about
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
