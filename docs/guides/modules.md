# Ein Modul anlegen

Ein Modul erhält einen eigenen Bereich unter `src/Module`:

```php
namespace App\Module\Billing;

use Nexum\Module\Attribute\NexumModule;

#[NexumModule(description: 'Billing domain')]
final class BillingModule
{
}
```

Die ID `billing` wird aus dem Klassennamen abgeleitet. Abhängigkeiten verwenden Class-Strings und werden transitiv ergänzt:

```php
#[NexumModule(dependencies: [UserModule::class])]
final class BillingModule
{
}
```

Nur Abweichungen werden konfiguriert:

```yaml
nexum:
    modules:
        billing:
            enabled: false
```

Controller, Entities und Services können beliebig tief innerhalb des Modulordners liegen und werden beim Container-Build entdeckt. Mit `enabled: false` wird ein aktiver Graph, der dieses Modul benötigt, bewusst zum Build-Fehler. Klassische Controller unter `src/Controller` bleiben beim normalen Symfony-Routenimport.

Projects sind optional. Ein Descriptor unter `src/Project/Storefront/StorefrontProject.php` weist Shared Modules explizit zu; Module unter `src/Project/Storefront/Module/` sind automatisch privat. Project-Zuordnung filtert Routen und `ProjectContext::hasModule()`, nicht globale Services, Subscriber, Handler oder Commands.
