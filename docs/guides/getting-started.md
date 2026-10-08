# Einstieg

Sillbeam kann in einer bestehenden Symfony-Anwendung installiert werden. PHP 8.4+ und Symfony 7.4/8.x sind erforderlich. Bundle und Route-Loader einmal registrieren; die [README](../../README.md) zeigt die drei Einträge. `sillbeam: {}` genügt.

Erstelle `src/Module/Hello/HelloModule.php`:

```php
namespace App\Module\Hello;

#[\Sillbeam\Module\Attribute\SillbeamModule]
final class HelloModule {}
```

Lege normale Services im Modulverzeichnis ab und Controller mit Symfony-Route-Attributen unter `Controller/`. `#[Exclude]`, `#[When]`, `#[WhenNot]`, `#[AsAlias]`, Autowiring und Autoconfiguration verwenden Symfonys nativen Klassenloader. Native Service-Konfiguration kann die Defaults überschreiben. Automatische Interface-Aliase entstehen erst nach allen Sillbeam-Importen und nur bei genau einer Implementierung.

```bash
php bin/console cache:clear
php bin/console sillbeam:architecture --format=json
php bin/console debug:router
```

DoctrineBundle aktiviert die Mapping-Integration automatisch. `sillbeam: { doctrine: false }` schaltet nur Sillbeams automatische Mappings aus. Native Mappings bleiben verfügbar.
