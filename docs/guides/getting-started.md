<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Erste Schritte

## Voraussetzungen

- PHP 8.4 oder neuer
- Composer 2
- Symfony 7.4
- je nach aktivierter Integration DoctrineBundle, SecurityBundle und die entsprechenden Symfony-Komponenten

Das Framework wird als einzelnes Package installiert:

```bash
composer require benjaminoeffinger/nexum
```

## Ein Bundle registrieren

Bis die externe Flex-Recipe veröffentlicht ist, wird genau ein Bundle eingetragen:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Nexum\NexumBundle::class => ['all' => true],
];
```

Eine minimale Konfiguration ohne optionale Integrationen:

```yaml
# config/packages/nexum.yaml
nexum:
    doctrine: { enabled: false }
    api: { enabled: false }
    user: { enabled: false }
    security: { enabled: false }
    management: { enabled: false }
```

Der Nexum-Routenloader wird einmalig importiert:

```yaml
# config/routes/nexum.yaml
nexum:
    resource: .
    type: nexum
```

Normale Controller unter `src/Controller` bleiben beim üblichen Symfony-Import. Nexum importiert ausschließlich Modul-, Project- und bewusst freigeschaltete Framework-Routen.

## Das erste Modul

```php
// src/Module/Billing/BillingModule.php
namespace App\Module\Billing;

use Nexum\Module\Attribute\NexumModule;

#[NexumModule(description: 'Billing domain')]
final class BillingModule
{
}
```

Die ID `billing` wird aus `BillingModule` abgeleitet. Die Descriptor-Klasse benötigt keine Basisklasse und wird nicht zum Symfony-Service.

Ein Service innerhalb des Moduls:

```php
namespace App\Module\Billing\Service;

final class InvoiceNumberGenerator
{
    public function next(): string
    {
        return 'INV-'.date('YmdHis');
    }
}
```

Nexum lädt Modulservices mit Symfonys `autowire` und `autoconfigure`. Commands, Event Subscriber und Messenger Handler erhalten dadurch ihre normalen Symfony-Tags.

Ein Controller:

```php
namespace App\Module\Billing\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class BillingController
{
    #[Route('/billing', name: 'billing_home')]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['module' => 'billing']);
    }
}
```

Ohne Project-Descriptoren läuft Nexum im **Application Mode**. Alle gefundenen und nicht deaktivierten Shared Modules sind aktiv; der Routenname bleibt `billing_home`.

## Optional: erstes Project

```php
// src/Project/Storefront/StorefrontProject.php
namespace App\Project\Storefront;

use App\Module\Billing\BillingModule;
use Nexum\Project\Attribute\NexumProject;

#[NexumProject(modules: [BillingModule::class])]
final class StorefrontProject
{
}
```

Sobald mindestens ein Project existiert, läuft Nexum im **Project Mode**. Routen erhalten das Präfix der Project-ID, hier `storefront.billing_home`. Bei mehreren Projects werden eindeutige Hosts konfiguriert:

```yaml
nexum:
    projects:
        storefront:
            hosts: [shop.example.test, www.example.test]
        admin:
            hosts: [admin.example.test]
```

Der erste Host ist der Canonical Host; weitere Hosts sind eingehende Aliase.

## Prüfen

```bash
php bin/console nexum:about
php bin/console nexum:architecture
php bin/console debug:router
php bin/console debug:container Nexum\\Module\\ModuleRegistryInterface
```

Weiterführend: [Module](modules.md), [Projects](projects.md), [Konfiguration](../reference/configuration.md) und [Test-Apps](../development/test-apps.md).
