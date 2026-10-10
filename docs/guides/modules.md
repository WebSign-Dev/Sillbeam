# Modules

Ein `#[SillbeamModule]`-Descriptor besitzt sein Verzeichnis samt Unterverzeichnissen. Dateiname und Descriptor-Klassenname müssen PSR-4-konform sein. IDs werden aus dem Klassennamen ohne `Module` abgeleitet oder als `id:` angegeben. Zwei Module dürfen weder dasselbe Verzeichnis noch verschachtelte Verzeichnisse besitzen.

```php
#[\Sillbeam\Module\Attribute\SillbeamModule(
    id: 'billing',
    description: 'Invoices',
    dependencies: [\App\Module\Payment\PaymentModule::class],
)]
final class BillingModule {}
```

Jeder direkte statische Zugriff auf eine Klasse eines anderen Moduls braucht dessen Descriptor in `dependencies`. Geprüft werden Typen (inklusive Union/Intersection), Vererbung, Interfaces, Traits, Attribute, `new`, statische Zugriffe, `::class`, `instanceof` und Catch-Typen. Dynamische Klassennamen, PHPDoc, String-Service-IDs sowie nicht modulgebundener Code sind außerhalb der Prüfung. Unbenutzte Imports zählen nicht; Descriptor-Metadaten werden nicht als Geschäftsreferenzen geprüft.

Shared Modules können nur Shared Modules verwenden. Private Modules dürfen Shared Modules und private Module desselben Projects verwenden. Fehlende Ziele, verbotene Grenzen und Zyklen sind Buildfehler. Aktiver Code darf kein inaktives Modul verwenden. Die Prüfung von Geschäftsreferenzen betrifft aktive Quellmodule; die deklarierte Architektur wird für den gesamten Katalog geprüft.

Ohne Projects sind alle aktivierten Shared Modules aktiv. Mit Projects bleiben nicht zugeordnete Module inaktiv. Deaktivieren:

```yaml
sillbeam:
    modules:
        billing: { enabled: false }
        'project:shop:reports': { enabled: false }
```

Ein aktives Modul, das ein deaktiviertes Modul benötigt, verhindert den Build. Ein deaktiviertes privates Modul ist kein automatischer Project-Root. Native explizite Imports können Sillbeams automatische Auswahl umgehen; solche Imports liegen in der Verantwortung der Anwendung.

IDs müssen `[a-z][a-z0-9-]*` entsprechen. Die automatische Ableitung entfernt `Module` und trennt CamelCase: `OrderHistoryModule` ergibt `order-history`. Ein aktiviertes, aber keinem Project zugeordnetes Shared Module bildet keinen aktiven Graphen; dessen deaktivierte Dependency allein verhindert keinen Build.
