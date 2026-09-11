<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Project-System

`Nexum\Project` modelliert hostbasierte Anwendungsoberflächen innerhalb eines Deployments.

## Descriptor und Definition

`#[NexumProject(modules: [...])]` nennt direkte Shared Modules. `ProjectDefinition` enthält zusätzlich transitive Module, Hosts, Namespace, Descriptor-, Controller- und Entity-Verzeichnisse. `canonicalHost()` liefert den ersten konfigurierten Host oder `null`.

## Registry

```php
$project = $projects->get('storefront');
$same = $projects->get(StorefrontProject::class);
$fromRequestHost = $projects->forHost('shop.example.com');
```

Bei einem Project ohne Hostliste löst `forHost()` jeden von Symfony akzeptierten Host auf dieses Project auf. Bei mehreren Projects wird ausschließlich eine exakte Zuordnung akzeptiert.

## ProjectContext

Der Context verwendet `RequestStack`, ProjectRegistry und ModuleRegistry. Er speichert keinen Prozesszustand:

```php
$context->current();
$context->id();
$context->is('admin');
$context->hasModule(CatalogModule::class);
```

Ohne Request – typischerweise in CLI – sind Project und ID `null`. Im Application Mode prüft `hasModule()` gegen den aktiven Application-Graph.

## Grenzen

Projects sind Routing- und Oberflächenbereiche, keine automatische Tenant-, Datenbank-, Cache-, Messenger- oder Token-Isolation.

Weiter: [Projects-Guide](../guides/projects.md), [Routing](routing.md) und [Test-Apps](../development/test-apps.md).
