# Ein Modul anlegen

Ein Modul erhält einen eigenen Bereich unter `src/Module`:

```php
namespace App\Module\Billing;

use Nexum\Kernel\Attribute\NexumModule;
use Nexum\Kernel\Module\AbstractModule;

#[NexumModule(id: 'billing', description: 'Billing domain')]
final class BillingModule extends AbstractModule
{
}
```

Danach wird es konfiguriert:

```yaml
version: 1

modules:
    billing:
        class: App\Module\Billing\BillingModule
        enabled: true
```

Controller können beliebig tief innerhalb des Modulordners liegen und werden automatisch über Symfony-Attribute geladen. Mit `enabled: false` verschwinden die Modulrouten beim nächsten Cache-Neubau. Klassische Controller unter `src/Controller` bleiben unabhängig davon kompatibel.
