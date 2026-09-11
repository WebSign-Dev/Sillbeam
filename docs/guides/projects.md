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
├── Module/
│   └── Catalog/                 # shared
└── Project/
    └── Storefront/
        ├── StorefrontProject.php
        ├── Controller/
        └── Module/
            └── Checkout/        # nur Storefront
                └── CheckoutModule.php
```

`src/Module/*` enthält Shared Modules. Sie gehören nur zu Projects, die sie im Descriptor nennen. `src/Project/<Project>/Module/*` ist automatisch privat und benötigt keine zusätzliche Zuordnung.

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

Ausführbare Beispiele: [Single Project](../../apps/single-project) und [Multi Project](../../apps/multi-project).
