# doctrine

When DoctrineBundle is present, direct Project entities and active modules are integrated using native AttributeDriver mappings. Dedicated drivers keep automatic exclusions local to Sillbeam and preserve manual native mappings. `doctrine: false` disables integration. There are no Sillbeam entity bases, stores, filters or listeners. [Configuration](../reference/configuration.md).

The application installs and configures Doctrine itself, e.g. `composer require symfony/orm-pack`. Tested dependency versions are listed in the [compatibility report](../development/package-compatibility-2026-10-10.md); the core package requires neither ORM nor database drivers. Entity classes under `src/Module/Hello/Entity/` use ordinary `Doctrine\ORM\Mapping` attributes:

```php
namespace App\Module\Hello\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public string $text = '';
}
```

This file begins with `<?php` and receives no automatic service registration. `php bin/console doctrine:mapping:info` checks the mapping; schema/migration generation and database access use standard Doctrine tools. Sillbeam neither creates tables nor runs migrations. Native mappings in `src/Entity/` remain intact. Broad manual mappings across all module directories can include disabled areas again.
