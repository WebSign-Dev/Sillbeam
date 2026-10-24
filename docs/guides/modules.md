# Modules

Create a minimal descriptor with `php bin/console sillbeam:make:module User`. For a private module, use `php bin/console sillbeam:make:module Checkout --project=Shop`. See [CLI creation rules](../reference/cli.md#minimal-module-and-project-creation).

A `#[SillbeamModule]` descriptor owns its directory and subdirectories. The filename and descriptor class name must comply with PSR-4. IDs are derived from the class name without `Module` or specified as `id:`. Two modules may own neither the same directory nor nested directories.

```php
#[\Sillbeam\Core\Module\Attribute\SillbeamModule(
    id: 'billing',
    description: 'Invoices',
    dependencies: [\App\Module\Payment\PaymentModule::class],
)]
final class BillingModule {}
```

Every direct static access to a class in another module requires its descriptor in `dependencies`. Validation covers types (including unions/intersections), inheritance, interfaces, traits, attributes, `new`, static access, `::class`, `instanceof` and catch types. Dynamic class names, PHPDoc, string service IDs and code outside module areas are outside validation. Unused imports do not count; descriptor metadata is not checked as business code references.

Shared modules may use only shared modules. Private modules may use shared modules and private modules of the same Project. Missing targets, forbidden boundaries and cycles are build errors. Active code must not use an inactive module. Business code reference validation applies to active source modules; declared architecture is validated for the entire catalog.

Without Projects, all enabled shared modules are active. With Projects, unassigned modules remain inactive. To disable modules:

```yaml
sillbeam:
    modules:
        billing: { enabled: false }
        'project:shop:reports': { enabled: false }
```

An active module that requires a disabled module prevents the build. A disabled private module is not an automatic Project root. Explicit native imports can bypass Sillbeam's automatic selection; such imports are the application's responsibility.

IDs must match `[a-z][a-z0-9-]*`. Automatic derivation removes `Module` and splits CamelCase: `OrderHistoryModule` becomes `order-history`. An enabled shared module not assigned to any Project does not form an active graph; its disabled dependency alone does not prevent a build.

Static class references in Module and Project code (including descriptor bodies)
also produce `code.unknown_class` when the type cannot be resolved from discovery,
loaded PHP types or Composer. This includes missing constructor types and applies
to inactive modules too. Optional attribute names and development-only `Tests`/`Fixtures`
directories are exempt from class resolution. Findings include file/line evidence and appear alongside
other architecture errors in text, JSON and Markdown reports. See
[class-resolution scope](../reference/architecture-analysis.md#existing-checks-and-identifiers).
