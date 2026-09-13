<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Projects und Multi-Domain-Routing

Projects teilen eine Symfony-Anwendung in hostbasierte Oberflächen. Sie sind sinnvoll für Bereiche wie Storefront, Administration und öffentliche API, die gemeinsam deployt werden, aber unterschiedliche Controller und Module sichtbar machen.

## Project-Descriptor

```php
namespace App\Project\Storefront;

use App\Module\Catalog\CatalogModule;
use App\Module\User\UserModule;
use Nexum\Project\Attribute\NexumProject;

#[NexumProject(
    description: 'Public storefront',
    modules: [CatalogModule::class, UserModule::class],
)]
final class StorefrontProject
{
}
```

Die ID wird aus `StorefrontProject` als `storefront` abgeleitet. Nur direkte Modules werden genannt; transitive Dependencies ergänzt Nexum.

## Shared und private Modules

```text
src/
├── Controller/                    # normales Symfony
├── Entity/
├── Service/
├── Module/
│   └── Catalog/                 # shared
└── Project/
    └── Storefront/
        ├── StorefrontProject.php
        ├── Controller/
        ├── Entity/
        ├── Service/
        └── Module/
            └── Checkout/        # nur Storefront
                ├── CheckoutModule.php
                ├── Controller/
                ├── Entity/
                └── Service/
```

`src/Module/*` enthält Shared Modules. Sie gehören nur zu Projects, die sie im Descriptor nennen. `src/Project/<Project>/Module/*` ist automatisch privat und benötigt keine zusätzliche Zuordnung.

Ein Project muss kein Module besitzen. Kleine, unmittelbar zur Oberfläche gehörende Controller und Services liegen direkt im Project. Erst ein fachlich abgegrenzter, nur dort verwendeter Bereich erhält ein privates Module. Soll dieser Bereich später von mehreren Projects verwendet werden, wird er nach `src/Module` verschoben und in den Project-Descriptoren ausdrücklich zugeordnet.

Nexum behandelt Project-eigene Verzeichnisse und private Modulbäume getrennt. `Project/Storefront/Controller` und `Project/Storefront/Entity` gehören zum Project; gleichnamige Verzeichnisse unter `Project/Storefront/Module/Checkout` gehören ausschließlich zum Checkout-Module. Dadurch werden Controller-Routen und Doctrine-Mappings nicht doppelt importiert.

## Erlaubte Hierarchie

Es gibt genau eine Project-Ebene:

```text
src/Project/<Project>/<Project>Project.php
src/Project/<Project>/Module/<Module>/<Module>Module.php
```

Ein weiteres `Project/` innerhalb eines Projects ist nicht erlaubt. Ebenso werden Modules nicht erneut ineinander verschachtelt. Ein mit `#[NexumProject]` oder `#[NexumModule]` markierter Descriptor außerhalb der erlaubten Struktur beendet den Container-Build mit einer konkreten Fehlermeldung, statt still ignoriert zu werden.

Die normale Symfony-Struktur bleibt parallel vollständig gültig. Sie sollte nicht als Legacy-Struktur verstanden werden: Nexum ergänzt sie optional und übernimmt ihren normalen Service- und Routenimport nicht.

Private Module-IDs dürfen sich zwischen Projects wiederholen. Intern bleibt die Class-String-Identität eindeutig; logische Schlüssel sehen beispielsweise so aus:

```text
shared:catalog
project:storefront:checkout
project:admin:checkout
```

## Hostauflösung

Bei genau einem Project ist keine Nexum-Hostkonfiguration erforderlich. Dieses Project akzeptiert jeden Host, den Symfonys `trusted_hosts` zulässt.

Bei mehreren Projects benötigt jedes Project mindestens einen eindeutigen exakten Host:

```yaml
nexum:
    projects:
        storefront:
            hosts:
                - shop.example.com
                - www.example.com
        admin:
            hosts:
                - admin.example.com
```

Für Storefront gilt:

- `shop.example.com` ist Canonical Host.
- `www.example.com` ist ein Inbound-Alias.
- Absolute URL-Generierung verwendet immer den Canonical Host.
- Ein unbekannter Host wird nicht still einem Default-Project zugewiesen.

Wildcards, Pfadpräfix-Projekte, Tenant-Subdomains und Custom Resolver gehören nicht zu dieser Version.

## Routennamen

Im Project Mode stellt Nexum dem ursprünglichen Symfony-Routennamen die Project-ID voran:

```text
catalog_list
↓
storefront.catalog_list
admin.catalog_list
```

Controller unter dem normalen `src/Controller` bleiben vom Nexum-Präfix unberührt.

## ProjectContext

```php
use Nexum\Project\ProjectContextInterface;

final class NavigationBuilder
{
    public function __construct(private ProjectContextInterface $project)
    {
    }

    public function build(): array
    {
        return [
            'project' => $this->project->id(),
            'catalog' => $this->project->hasModule(CatalogModule::class),
        ];
    }
}
```

Der Context ist zustandslos. Er liest den aktuellen Request aus dem `RequestStack` und löst dessen Host über die Registry auf. Es gibt kein `setProject()` und keinen prozessweiten Zustand.

In CLI-Prozessen liefert `current()` beziehungsweise `id()` `null`. Commands, die ein Project benötigen, verwenden `ProjectRegistryInterface` ausdrücklich.

## Sicherheitsgrenze

Projects sind keine Mandantenisolierung. Datenzugriff, Tokens, Cache Keys und fachliche Autorisierung müssen weiterhin passende Scopes oder Tenant-Regeln verwenden. Siehe [REST-Daten-Scope](api.md#daten-scope) und [Security](security.md).

Ausführbare Beispiele: [Project ohne Modules](../../apps/project-only), [Single Project](../../apps/single-project) und [Multi Project](../../apps/multi-project).
