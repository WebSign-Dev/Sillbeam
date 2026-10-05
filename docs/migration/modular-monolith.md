# Migration zum Nexum-Modullayer

Diese Evolution ist eine Breaking Change. Vor einem Update die Nutzung der entfernten Bereiche erfassen, Ersatz implementieren und Anwendungen mit dem neuen Package prüfen. Vorhandene Datenbanken werden durch das Package nicht automatisch geändert; Schema-Migrationen verantwortet die Anwendung.

## Entfernte Foundation

Entfernt wurden die Namespaces `Nexum\Api`, `Core`, `Doctrine`, `Entity`, `Exception`, `Filesystem`, `Health`, `Http`, `Location`, `Logger`, `Mailer`, `Management`, `Messenger`, `Notification`, `Payment`, `QrCode`, `Reporting`, `Search`, `Security`, `Serializer`, `User` und `Validator` samt zugehöriger Konfiguration. Alte Versionen bleiben im Git-Verlauf verfügbar.

- API-Resource-/CRUD-Basen durch Symfony-Controller oder ein explizit gewähltes API-Package ersetzen.
- Entity-Vererbung, UUID-/Timestamp-/Soft-Delete-Mapping und EntityStores durch eigene Entities, Repositories, Listener/Filter ersetzen. Spalten und bestehende IDs zunächst erhalten; ORM-Schemadiff vor Ausführung prüfen.
- User, AccessToken, MFA und Passkey-Funktionen mit eigenem Security-/Persistenzkonzept migrieren. Bestehende Credentials/verschlüsselte Geheimnisse und deren Schlüssel nicht verlieren. Nexum installiert keine Authentifizierung mehr.
- Infrastrukturverträge und Adapter in der Anwendung oder einem separaten Package unter eigenem Namespace betreiben; direkte Symfony-/Provider-Konfiguration verwenden.
- Foundation-Keys aus `nexum.yaml` entfernen: `api`, `user`, `security`, `management`, `filesystem`, `mailer`, `messenger`, `qrcode`, `http`, `serializer`, `validator` und weitere frühere Integrationen. Der neue Konfigurationsbaum akzeptiert nur Discovery, Module-Aktivierung, Projects, Integrationspatterns und Doctrine-Mapping.

Strata ist keine verfügbare Ersatzimplementierung und kein Pflichtpackage.

## Architektur und Imports

Module-/Project-Attribute bleiben erhalten. PSR-4-Dateinamen sind frei; Discovery erkennt die Attribute statt einer Dateinamenskonvention. Klassennamen ohne `Module`-/`Project`-Suffix brauchen bei gewünschter alter ID ein explizites `id:`.

Standardpfade bleiben `src/Module`/`src/Project`. [Konfiguration](../reference/configuration.md) erlaubt weitere Roots und Interiors. Überlappende Modulbereiche und private Descriptoren ohne Project werden jetzt abgelehnt. Fachliche Unterordner bleiben frei.

Direkte statische Modulzugriffe brauchen direkte `dependencies`; transitive Abhängigkeiten genügen nicht. Fehlermeldungen nennen die Quelle. Nicht verwendete Imports bleiben folgenlos. Shared→Private und private Zugriffe über Project-Grenzen sind verboten.

Deaktivierte private Module werden nicht mehr automatisch aktiviert. Aktive Module mit deaktivierten Dependencies bleiben ein Buildfehler. Doctrine mappt automatisch nur aktive Module und direkte Project-Entities. Bei installiertem DoctrineBundle ist Nexum-Mapping nun standardmäßig aktiv; für bisherige rein native Konfiguration `doctrine: false` setzen.

`#[Exclude]`, `#[When]`, `#[WhenNot]`, `#[AsAlias]` und automatische Interface-Aliase folgen dem nativen Symfony-Loader. Prüfe `debug:container` nach dem Update. Manuelle Symfony-/Doctrine-Imports können automatische Ausschlüsse bewusst umgehen.

## Validierung

1. Composer-Abhängigkeiten aktualisieren; Doctrine bei Bedarf ausdrücklich in der Anwendung installieren.
2. Cache neu bauen und statisch gemeldete Abhängigkeiten korrigieren.
3. `nexum:architecture --format=json`, `debug:container`, `debug:router` und bei Doctrine `doctrine:mapping:info` vergleichen.
4. Anwendungs-, Authentifizierungs- und Persistenztests ausführen; Datenbankänderungen separat prüfen.

Die Testanwendung verwendet jetzt gewöhnliche Symfony-Controller und native Doctrine-Entities; [Test-Apps](../development/test-apps.md) zeigen die unterstützten Betriebsformen.
