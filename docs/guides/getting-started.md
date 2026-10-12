# Einstieg

Sillbeam kann in einer bestehenden Symfony-Anwendung installiert werden. PHP 8.4+ und Symfony 7.4/8.x sind erforderlich. Bundle und Route-Loader einmal registrieren; die [README](../../README.md) zeigt die drei Einträge. `sillbeam: {}` genügt.

Erstelle `src/Module/Hello/HelloModule.php`:

```php
namespace App\Module\Hello;

#[\Sillbeam\Module\Attribute\SillbeamModule]
final class HelloModule {}
```

Lege normale Services im Modulverzeichnis ab und Controller mit Symfony-Route-Attributen unter `Controller/`. `#[Exclude]`, `#[When]`, `#[WhenNot]`, `#[AsAlias]`, Autowiring und Autoconfiguration verwenden Symfonys nativen Klassenloader. Native Service-Konfiguration kann die Defaults überschreiben. Automatische Interface-Aliase entstehen erst nach allen Sillbeam-Importen und nur bei genau einer Implementierung.

```bash
php bin/console cache:clear
php bin/console sillbeam:architecture --format=json
php bin/console debug:router
```

DoctrineBundle aktiviert die Mapping-Integration automatisch. `sillbeam: { doctrine: false }` schaltet nur Sillbeams automatische Mappings aus. Native Mappings bleiben verfügbar.

## Installation und Autoloading

Für die Entwicklungsversion das lokale Path-Repository und `dev-main` aus der [README](../../README.md#install-from-a-checkout) verwenden. Nach Veröffentlichung gilt `composer require websign-dev/sillbeam:^0.1`; diese Version ist derzeit noch nicht öffentlich verfügbar. Flex-Recipe und Package werden unabhängig veröffentlicht; manuelle Bundle-/Konfigurations-/Routing-Registrierung funktioniert bereits jetzt.

Die Anwendung braucht eine Composer-PSR-4-Zuordnung wie `"App\\": "src/"`. Alle folgenden PHP-Dateien beginnen mit `<?php`. Den bestehenden Symfony-Kernel beibehalten. Optional kann `src/Kernel.php` so aussehen:

```php
namespace App;

final class Kernel extends \Sillbeam\Kernel\SillbeamKernel {}
```

## Service und Route ausprobieren

Ergänze `src/Module/Hello/Greeting.php`:

```php
namespace App\Module\Hello;

final class Greeting
{
    public function message(): string
    {
        return 'Hello from Sillbeam';
    }
}
```

Und `src/Module/Hello/Controller/HelloController.php`:

```php
namespace App\Module\Hello\Controller;

use App\Module\Hello\Greeting;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HelloController
{
    public function __construct(private Greeting $greeting) {}

    #[Route('/hello', name: 'hello', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->greeting->message());
    }
}
```

Nach `php bin/console cache:clear` zeigt `php bin/console debug:router hello` die Route. Eine Anfrage an `/hello` liefert `Hello from Sillbeam`. Mit Projects wird das Modul im jeweiligen Project-Descriptor zugeordnet; dort erhält die Route dessen Namenspräfix und gegebenenfalls Hostfilter.

## Symfony-Integration erhalten

Globale `App\`-Service-Imports in `config/services.yaml` müssen `src/Module/` und `src/Project/` ausschließen, wenn allein Sillbeam deren Aktivierung steuern soll. Beispiel für die Standardverzeichnisse:

```yaml
services:
    _defaults: { autowire: true, autoconfigure: true }
    App\:
        resource: '../src/'
        exclude: '../src/{DependencyInjection,Entity,Kernel.php,Module,Project}'
```

Bestehende Ausschlüsse/Services beibehalten und bei eigenen Roots entsprechend anpassen. Native breite Route- und Doctrine-Imports ebenso prüfen: explizite Symfony-Imports können bewusst inaktive Bereiche wieder aufnehmen. Sillbeam entfernt solche Anwendungskonfiguration nicht. Für die Modulrouten genügt der einmalige `type: sillbeam`-Import.

Ein unveränderter Skeleton-Import von ganz `src/` bleibt eine unabhängige Symfony-Registrierung: Ein dort gefundenes `#[AsAlias]` kann beispielsweise auch bei `enabled: false` als Service erreichbar bleiben. Überlappende Imports erzeugen nicht zwei Instanzen pro Service-ID; die native Definition ist maßgeblich, kann aber Sillbeams Aktivierung und Standardausschlüsse umgehen. Die Bereichsausschlüsse sind deshalb erforderlich, wenn Sillbeam die automatische Registrierung kontrollieren soll, auch im Project-Modus für nicht zugeordnete Shared-Module und deaktivierte private Module.

Innerhalb aktiver Bereiche verwendet Sillbeam den Symfony-Service-Loader. `#[Exclude]`, `#[When]`, `#[WhenNot]` und `#[AsAlias]` gelten weiterhin. Explizite Service-Definitionen, Constructor-Argumente und Interface-Aliase in der Anwendung haben Vorrang; ein bewusst manuell registrierter Service ist ein Escape-Hatch auch für einen deaktivierten Bereich. Die Flex-Recipe ändert bestehende Serviceimports nicht: Die Ausschlüsse bei der Integration selbst ergänzen, vorhandene Ausschlüsse beibehalten. Ein normaler Symfony-Kernel mit `MicroKernelTrait` genügt.

Für native Doctrine-Mappings möglichst die tatsächlich nativen Entity-Verzeichnisse und Namespace-Präfixe verwenden; ein breites Mapping von `src/` kann auch inaktive Modul-Entities einbeziehen. Explizite Overrides generierter Mappings bleiben maßgeblich. Ohne registriertes DoctrineBundle entstehen keine automatischen Mappings; `sillbeam.doctrine: true` ohne Bundle ist ein Konfigurationsfehler.

Development-Caches überwachen Quell-, Konfigurations- und Routing-Ressourcen. Production-Caches werden bei Deployments mit geändertem Code, Routing, Composer-Mapping oder Aktivierung neu gebaut; sie sind kein Live-Discovery-Mechanismus. Auch Änderungen an Build-Environment-Werten erfordern einen Neubau.
