# Einstieg

Nexum kann in einer bestehenden Symfony-Anwendung installiert werden. PHP 8.4+ und Symfony 7.4/8.x sind erforderlich. Bundle und Route-Loader einmal registrieren; die [README](../../README.md) zeigt die drei Einträge. `nexum: {}` genügt.

Erstelle `src/Module/Hello/HelloModule.php`:

```php
namespace App\Module\Hello;

#[\Nexum\Module\Attribute\NexumModule]
final class HelloModule {}
```

Lege normale Services im Modulverzeichnis ab und Controller mit Symfony-Route-Attributen unter `Controller/`. `#[Exclude]`, `#[When]`, `#[WhenNot]`, `#[AsAlias]`, Autowiring und Autoconfiguration verwenden Symfonys nativen Klassenloader. Native Service-Konfiguration kann die Defaults überschreiben. Automatische Interface-Aliase entstehen erst nach allen Nexum-Importen und nur bei genau einer Implementierung.

```bash
php bin/console cache:clear
php bin/console nexum:architecture --format=json
php bin/console debug:router
```

DoctrineBundle aktiviert die Mapping-Integration automatisch. `nexum: { doctrine: false }` schaltet nur Nexums automatische Mappings aus. Native Mappings bleiben verfügbar.
