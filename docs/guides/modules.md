<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Modules

Modules schneiden fachliche Bereiche innerhalb einer Symfony-Anwendung. Sie sind keine Composer-Packages und keine Microservices.

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

## Konventionelle Struktur

```text
src/Module/Billing/
├── BillingModule.php
├── Command/
├── Controller/
├── Dto/
├── Entity/
├── EventSubscriber/
├── MessageHandler/
├── Repository/
└── Service/
```

`Entity/`, `Dto/` und der Descriptor werden nicht als Services registriert. Die übrigen konkreten Klassen erhalten normales Symfony-Autowiring und Autoconfiguration.

## Identität und Schlüssel

Class-Strings sind die technische Identität und werden für Dependencies sowie Registry-Zugriffe verwendet. IDs sind menschenlesbare Namen für Konfiguration, Routennamen, CLI und Debugging.

```php
$definition = $registry->get(BillingModule::class);
```

Die automatisch abgeleitete ID kann überschrieben werden:

```php
#[NexumModule(id: 'invoicing')]
final class BillingModule
{
}
```

IDs müssen lowercase kebab-case entsprechen.

## Abhängigkeitsgraph

```php
#[NexumModule(dependencies: [UserModule::class])]
final class PaymentModule
{
}
```

Wenn ein Project `PaymentModule` verwendet, ergänzt Nexum `UserModule` transitiv. Fehlende Klassen, Zyklen und ungültige Scope-Richtungen stoppen den Container-Build.

```text
Payment → User → Security
```

Shared Modules dürfen nicht von project-privaten Modules abhängen. Private Modules dürfen private Dependencies desselben Projects verwenden, aber keine eines anderen Projects.

## Laufzeitwirkung

Im Application Mode enthält `forApplication()` alle aktiven Shared Modules. Im Project Mode liefert `forProject(ProjectClass::class)` die direkte und transitive Zuordnung. Verwendete Modulservices bleiben containerweit verfügbar; Subscriber, Handler und Commands laufen global.

## Cacheverhalten

Descriptor-Dateien, Modulverzeichnisse und Controllerverzeichnisse werden als Container- beziehungsweise Router-Ressourcen registriert. In Development invalidieren neue, geänderte oder gelöschte Dateien die zuständigen Symfony-Caches.

Projects sind optional. Ein Descriptor unter `src/Project/Storefront/StorefrontProject.php` weist Shared Modules explizit zu; Module unter `src/Project/Storefront/Module/` sind automatisch privat. Project-Zuordnung filtert Routen und `ProjectContext::hasModule()`, nicht globale Services, Subscriber, Handler oder Commands.

Module dürfen nicht ineinander verschachtelt werden. Zulässige Descriptoren liegen ausschließlich unter `src/Module/<Module>/*Module.php` oder `src/Project/<Project>/Module/<Module>/*Module.php`. Attributierte Descriptoren außerhalb dieser Konvention führen beim Container-Build zu einem Fehler.

Weiter: [Projects](projects.md), [Architektur](../architecture/overview.md), [Konfiguration](../reference/configuration.md) und [Modules-only-Beispiel](../../apps/modules-only).
