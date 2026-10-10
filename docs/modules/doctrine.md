# doctrine

Bei vorhandenem DoctrineBundle werden direkte Project-Entities und aktive Module mit nativen AttributeDriver-Mappings integriert. Dedizierte Treiber halten automatische Ausschlüsse lokal zu Sillbeam und erhalten manuelle native Mappings. `doctrine: false` schaltet die Integration aus. Es gibt keine Sillbeam-Entity-Basen, Stores, Filter oder Listener. [Konfiguration](../reference/configuration.md).

Die Anwendung installiert und konfiguriert Doctrine selbst, z. B. `composer require symfony/orm-pack`. Die geprüften Dependency-Versionen stehen im [Kompatibilitätsbericht](../development/package-compatibility-2026-10-10.md); das Core-Package erzwingt weder ORM noch Datenbanktreiber. Entity-Klassen unter `src/Module/Hello/Entity/` verwenden normale `Doctrine\ORM\Mapping`-Attribute:

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

Diese Datei beginnt mit `<?php` und erhält keine automatische Service-Registrierung. `php bin/console doctrine:mapping:info` prüft das Mapping; Schema-/Migrationserstellung und Datenbankzugriff erfolgen mit normalen Doctrine-Werkzeugen. Sillbeam erstellt keine Tabellen und führt keine Migrationen aus. Native Mappings in `src/Entity/` bleiben bestehen. Breite manuelle Mappings über alle Modulverzeichnisse können deaktivierte Bereiche wieder aufnehmen.
