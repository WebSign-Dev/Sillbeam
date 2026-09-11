<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Migration zum modularen Monolithen

Diese Migration ist ein direkter Breaking Cut für die Foundation-Phase. Es gibt keine Übergangsbundles oder Alias-Packages.

## Vorher und nachher

| Vorher | Nachher |
| --- | --- |
| viele `nexum/*` Composer-Packages | `benjaminoeffinger/nexum` |
| viele `Nexum*Bundle` | `Nexum\NexumBundle` |
| `packages/<component>/src` | `src/<Component>` |
| `Nexum\Entity\Entity\*` | `Nexum\Entity\*` |
| `config/nexum/modules.yaml` | Descriptor-Discovery |
| YAML-Klassenregistrierung | `#[NexumModule]` und `#[NexumProject]` |
| Runtime Enable/Disable | Compile-time-Konfiguration |
| Kernel-eigenes Modulrouting | zentraler `type: nexum` Route Loader |
| mutierendes Management | read-only Architekturansicht |

## Composer

```bash
composer remove nexum/core nexum/entity nexum/security
composer require benjaminoeffinger/nexum
```

Anschließend alle alten Nexum-Teilbundles aus `config/bundles.php` entfernen und ausschließlich Folgendes registrieren:

```php
Nexum\NexumBundle::class => ['all' => true],
```

## Entity-Imports

```diff
- use Nexum\Entity\Entity\AbstractSoftDeletableEntity;
+ use Nexum\Entity\AbstractSoftDeletableEntity;
```

Doctrine-Prefixe und eigene Mapping-Konfigurationen entsprechend aktualisieren. Bei aktivierter Nexum-Doctrine-Integration erkennt Nexum Module- und Project-Entity-Verzeichnisse automatisch.

## Module migrieren

Aus einer YAML-Registrierung:

```yaml
modules:
    billing:
        class: App\Module\Billing\BillingModule
        enabled: true
```

wird ein Descriptor:

```php
#[NexumModule]
final class BillingModule
{
}
```

Der Descriptor liegt unter `src/Module/Billing/BillingModule.php`. Services, Commands, Subscriber, Handler, Controller und Entities verbleiben im Modulverzeichnis.

## Projects einführen

Projects sind optional. Besteht die Anwendung ausschließlich aus Shared Modules, ist keine künstliche Project-Klasse erforderlich.

Für mehrere Hostoberflächen:

```php
#[NexumProject(modules: [BillingModule::class, UserModule::class])]
final class AdminProject
{
}
```

Private Module werden nach `src/Project/Admin/Module/<Name>` verschoben. Shared Modules dürfen nicht von privaten Modules abhängen; private Modules dürfen keine privaten Modules eines anderen Projects verwenden.

## Routing migrieren

Alle alten Nexum-spezifischen Routenimports durch einen Import ersetzen:

```yaml
nexum:
    resource: .
    type: nexum
```

Den normalen Symfony-Controllerimport beibehalten. Im Project Mode ändern sich Modulroutennamen durch das `<project-id>.`-Präfix; diese Namen in Links, Redirects und Tests aktualisieren.

## Security bewusst aktivieren

Installation allein veröffentlicht keine Security-Endpunkte. Fähigkeiten und APIs separat konfigurieren. Im Project Mode muss jede exponierende API eine Project-Allowlist oder ausdrücklich `['*']` besitzen.

## Entfernen

Folgende Altbestandteile dürfen nach erfolgreicher Migration gelöscht werden:

- `config/nexum/modules.yaml`
- `YamlModuleRegistry`
- `ModuleConfigurationManager`
- Runtime-Enable/Disable-Kommandos
- HTTP-Mutationsendpunkte des alten Managements
- alle alten Bundle-spezifischen Konfigurationsdateien

## Abnahme

1. Stock-Symfony-Kernel bootet mit genau einem `NexumBundle`.
2. `nexum:architecture` zeigt alle erwarteten Module und Projects.
3. `debug:router` enthält nur bewusst exponierte Routen.
4. Unbekannte Multi-Project-Hosts schlagen fehl.
5. Doctrine-Mappings enthalten alle erwarteten Entity-Verzeichnisse.
6. PHPUnit, PHPStan, Deptrac und Coding Style sind grün.

Konkrete Zielzustände stehen in den [Test-Apps](../development/test-apps.md).
