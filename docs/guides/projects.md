# Projects

Projects bündeln Oberflächen in einer gemeinsamen Symfony-Anwendung. Der Descriptor besitzt sein Verzeichnis; dessen Services, Controller und Entities werden direkt integriert. Ein Project darf nicht innerhalb eines Moduls liegen. Private Module dürfen innerhalb des Projects beliebig liegen, solange ihre Verzeichnisse nicht mit anderen Modulen überlappen.

```php
namespace App\Project\Shop;

#[\Sillbeam\Project\Attribute\SillbeamProject(
    modules: [\App\Module\Billing\BillingModule::class],
)]
final class ShopProject {}
```

Private Module werden über physische Zugehörigkeit zum Project erkannt und bei Aktivierung automatisch Teil seines Graphen. Ein Modul unter einer konfigurierten Project-Grenze ohne owning Project ist ein Fehler. IDs privater Module sind innerhalb eines Projects eindeutig; die Konfigurationsadresse ist `project:<project-id>:<module-id>`.

```yaml
sillbeam:
    projects:
        shop: { hosts: [shop.example.com] }
        admin: { hosts: [admin.example.com] }
```

Bei mehreren Projects braucht jedes mindestens einen eindeutigen Host. Das erste Element ist der kanonische Host. Routen erhalten den Präfix `<project-id>.`; Shared-Module-Routen werden für jede zugeordnete Oberfläche importiert. Ein einzelnes Project darf ohne Hostfilter laufen.

`ProjectContextInterface` liefert `current()`, `id()`, `is(id)` und `hasModule(class)` im aktuellen Request. CLI hat ohne Request kein aktuelles Project. Alle Projects teilen Container, Datenbank und Event-System; globale Subscriber/Handler laufen nicht automatisch nur für einen Host.
