<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Kernel

`Nexum\Kernel` enthält einen optionalen Komfort-Kernel, Runtime-Informationen und `nexum:about`. Nexum setzt keinen eigenen Kernel voraus.

## Empfohlener Stock-Kernel

```php
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
```

Zusammen mit `Nexum\NexumBundle` besitzt er die vollständige Nexum-Funktionalität.

## Optionaler Komfort-Kernel

```php
use Nexum\Kernel\NexumKernel;

final class Kernel extends NexumKernel
{
}
```

`NexumKernel` ergänzt im Wesentlichen `getApplicationName()` anhand des Composer-Root-Packages. Er besitzt kein eigenes Modulrouting und erzeugt keine zweite Integrationsvariante.

## Runtime-Informationen

`KernelInformationInterface::all()` liefert Anwendung, Environment, Debugstatus, PHP-, Symfony- und Nexum-Version sowie gefundene und aktive Modulzahlen. `AboutCommand` formatiert diese Informationen:

```bash
php bin/console nexum:about
```

## Grenzen

- Kein Service Locator im Kernel.
- Kein statisches `Nexum::service()`.
- Kein eigener Router oder Container.
- Kein Unterschied im Verhalten zwischen Stock-Kernel und NexumKernel.

Weiter: [Erste Schritte](../guides/getting-started.md), [Architektur](../architecture/overview.md) und [Test-Apps](../development/test-apps.md).
