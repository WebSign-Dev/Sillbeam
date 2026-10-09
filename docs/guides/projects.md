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

`ProjectRegistryInterface::get()` und `forHost()` werfen bei unbekannten Projects beziehungsweise nicht zugeordneten Hosts eine `OutOfBoundsException`. `forHost()` vergleicht Hosts ohne Beachtung der Groß-/Kleinschreibung; ein einzelnes Project ohne Hosts gilt für jeden Host.

Der Context gibt ohne Projects oder ohne aktuellen Request `null` für `current()` und `id()` sowie `false` für `is()` zurück. Sind Projects vorhanden und passt der Request-Host zu keinem davon, reichen alle Context-Methoden die `OutOfBoundsException` weiter. Es gibt keinen Fallback auf ein beliebiges Project. `hasModule()` prüft ohne aktuellen Project-Kontext ausschließlich den Anwendungsgraphen; im Project-Modus ist dieser leer. In Hybrid-Anwendungen können native Symfony-Routen auch auf anderen Hosts laufen; Code, der dort den ProjectContext abfragt, muss den unbekannten Host explizit behandeln. Der Context folgt dem jeweils aktuellen Request, auch bei Subrequests.
