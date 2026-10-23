# Projects

Projects group application surfaces within a shared Symfony application. The descriptor owns its directory; its services, controllers and entities are integrated directly. A Project must be neither inside a module nor inside another Project; Project descriptors must not share a directory. Private modules may be located anywhere within the Project as long as their directories do not overlap other modules.

```php
namespace App\Project\Shop;

#[\Sillbeam\Core\Project\Attribute\SillbeamProject(
    modules: [\App\Module\Billing\BillingModule::class],
)]
final class ShopProject {}
```

Private modules are identified by their physical ownership within the Project and automatically join its graph when enabled. A module under a configured Project boundary without an owning Project is an error. Private module IDs are unique within a Project; their configuration address is `project:<project-id>:<module-id>`.

```yaml
sillbeam:
    projects:
        shop: { hosts: [shop.example.com] }
        admin: { hosts: [admin.example.com] }
```

With multiple Projects, each needs at least one unique host. The first entry is the canonical host. Routes receive the prefix `<project-id>.`; shared module routes are imported for every assigned application surface. A single Project may run without a host filter.

`ProjectContextInterface` provides `current()`, `id()`, `is(id)` and `hasModule(class)` in the current request. CLI has no current Project without a request. All Projects share the container, database and event system; global subscribers/handlers are not automatically scoped to a host.

`ProjectRegistryInterface::get()` and `forHost()` throw `OutOfBoundsException` for unknown Projects or unassigned hosts. `forHost()` compares hosts case-insensitively; a single Project without hosts applies to every host.

Without Projects or a current request, the context returns `null` for `current()` and `id()` and `false` for `is()`. When Projects exist and the request host matches none of them, all context methods propagate `OutOfBoundsException`. There is no fallback to an arbitrary Project. Without a current Project context, `hasModule()` checks only the application graph; in Project mode that graph is empty. In Hybrid applications, native Symfony routes can also run on other hosts; code querying ProjectContext there must explicitly handle the unknown host. The context follows the current request, including subrequests.

Project IDs match `[a-z][a-z0-9-]*`; `ShopProject` becomes `shop`. Hosts are exact hostnames, trimmed and lowercased. Do not configure URLs, wildcards, paths or ports: use `shop.example.com` rather than `https://shop.example.com`, `*.example.com` or `localhost:8000`.
