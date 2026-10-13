# Getting started

Sillbeam can be installed in an existing Symfony application. PHP 8.4+ and Symfony 7.4/8.x are required. Register the bundle and route loader once; the [README](../../README.md) shows bundle registration and the route import. A `config/packages/sillbeam.yaml` file is not required for the defaults.

Create `src/Module/Hello/HelloModule.php`:

```php
namespace App\Module\Hello;

#[\Sillbeam\Module\Attribute\SillbeamModule]
final class HelloModule {}
```

Place ordinary services in the module directory and controllers with Symfony route attributes under `Controller/`. `#[Exclude]`, `#[When]`, `#[WhenNot]`, `#[AsAlias]`, autowiring and autoconfiguration use Symfony's native class loader. Native service configuration can override the defaults. Automatic interface aliases are created after all Sillbeam imports and only when there is exactly one implementation.

```bash
php bin/console cache:clear
php bin/console sillbeam:architecture --format=json
php bin/console debug:router
```

DoctrineBundle activates mapping integration automatically. `sillbeam: { doctrine: false }` disables only Sillbeam's automatic mappings. Native mappings remain available.

## Installation and autoloading

For the development version, use the local path repository and `dev-main` from the [README](../../README.md#install-from-a-checkout). After publication, use `composer require websign-dev/sillbeam:^0.1`; this version is not yet publicly available. The Flex recipe and package are published independently; manual bundle/routing registration already works.

The application needs a Composer PSR-4 mapping such as `"App\\": "src/"`. All PHP files below begin with `<?php`. Keep the existing Symfony kernel. Optionally, `src/Kernel.php` can look like this:

```php
namespace App;

final class Kernel extends \Sillbeam\Kernel\SillbeamKernel {}
```

## Try a service and route

Add `src/Module/Hello/Greeting.php`:

```php
namespace App\Module\Hello;

final class Greeting
{
    public function message(): string
    {
        return 'Hello from Sillbeam';
    }
}
```

And `src/Module/Hello/Controller/HelloController.php`:

```php
namespace App\Module\Hello\Controller;

use App\Module\Hello\Greeting;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HelloController
{
    public function __construct(private Greeting $greeting) {}

    #[Route('/hello', name: 'hello', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response($this->greeting->message());
    }
}
```

After `php bin/console cache:clear`, `php bin/console debug:router hello` displays the route. A request to `/hello` returns `Hello from Sillbeam`. With Projects, assign the module in the relevant Project descriptor; the route receives that Project's name prefix and, if configured, host filter.

## Preserve Symfony integration

Global `App\` service imports in `config/services.yaml` must exclude `src/Module/` and `src/Project/` when Sillbeam alone should control their activation. Example for the default directories:

```yaml
services:
    _defaults: { autowire: true, autoconfigure: true }
    App\:
        resource: '../src/'
        exclude: '../src/{DependencyInjection,Entity,Kernel.php,Module,Project}'
```

Keep existing exclusions/services and adjust them for custom roots. Also review broad native route and Doctrine imports: explicit Symfony imports can intentionally include inactive areas again. Sillbeam does not remove such application configuration. A single `type: sillbeam` import is sufficient for module routes.

An unchanged skeleton import of all `src/` remains an independent Symfony registration: an `#[AsAlias]` found there, for example, can remain accessible as a service even with `enabled: false`. Overlapping imports do not create two instances per service ID; the native definition takes precedence, but can bypass Sillbeam's activation and default exclusions. Area exclusions are therefore required when Sillbeam should control automatic registration, including unassigned shared modules and disabled private modules in Project mode.

Within active areas, Sillbeam uses the Symfony service loader. `#[Exclude]`, `#[When]`, `#[WhenNot]` and `#[AsAlias]` still apply. Explicit service definitions, constructor arguments and interface aliases in the application take precedence; an intentionally registered manual service is an escape hatch even for a disabled area. The Flex recipe does not change existing service imports: add exclusions during integration and retain existing exclusions. A standard Symfony kernel with `MicroKernelTrait` is sufficient.

For native Doctrine mappings, prefer the actual native entity directories and namespace prefixes; a broad mapping of `src/` can include inactive module entities. Explicit overrides of generated mappings remain authoritative. Without a registered DoctrineBundle, no automatic mappings are created; `sillbeam.doctrine: true` without the bundle is a configuration error.

Development caches monitor source, configuration and routing resources. Production caches are rebuilt during deployments that change code, routing, Composer mappings or activation; they are not a live discovery mechanism. Changes to build environment values also require a rebuild.
