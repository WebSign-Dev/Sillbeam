<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Module-System

`Nexum\Module` beschreibt fachliche Application Modules und stellt ihren kompilierten Zustand lesend bereit.

## Descriptor

```php
#[NexumModule(
    id: 'billing',
    description: 'Billing domain',
    dependencies: [UserModule::class],
)]
final class BillingModule
{
}
```

Der Descriptor besitzt keinen Lifecycle und ist kein Service. ID und Description sind Metadaten; Dependencies verwenden Class-Strings.

## ModuleDefinition

Die immutable Definition enthält ID, Klasse, Description, Dependencies, Aktivstatus, Verzeichnis, Descriptor-Datei, Namespace, Entity-/Controller-Verzeichnisse und optional die besitzende Project-Klasse.

`key()` erzeugt `shared:<id>` oder `project:<project-id>:<id>`. `isPrivate()` zeigt den Project-Scope an.

## Registry

```php
$all = $registry->all();
$billing = $registry->get(BillingModule::class);
$application = $registry->forApplication();
$storefront = $registry->forProject(StorefrontProject::class);
$active = $registry->active();
```

Alle Registry-Methoden sind read-only. Unbekannte Klassen oder Projects schlagen explizit fehl; es gibt kein `refresh()` und keine Runtime-Dateizugriffe.

## Laufzeitgrenze

Im Project Mode steuert die Zuordnung Routen und `hasModule()`. Services eines verwendeten Modules sowie dessen Subscriber, Handler und Commands bleiben global kompiliert.

Weiter: [Modules-Guide](../guides/modules.md), [Architecture](architecture.md) und [Project-System](project-system.md).
