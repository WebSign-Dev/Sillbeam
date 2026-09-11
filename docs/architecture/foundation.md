# Nexum Foundation 0.1

## Ziel

Die Foundation soll häufigen Backend-Code stark reduzieren, ohne versteckte Datenbankoperationen oder einen überladenen Kernel einzuführen. Symfony bleibt Grundlage; Nexum wird als ein Package und ein `NexumBundle` integriert.

## Projekt- und Modulstruktur

Nexum unterstützt parallel zwei Strukturen:

```text
src/Controller/...
src/Entity/...

src/Module/Billing/BillingModule.php
src/Module/Billing/Invoice/Controller/...
src/Module/Billing/Invoice/Entity/...

src/Project/Storefront/StorefrontProject.php
src/Project/Storefront/Module/Checkout/CheckoutModule.php
```

Klassische Controller bleiben beim Symfony-Standardimport. Nexum entdeckt parameterlose Descriptor-Klassen per Filesystem, PSR-4 und Reflection. Descriptoren sind keine Services und benötigen keine Basisklasse. Ohne Projects sind alle nicht deaktivierten Shared Modules aktiv; mit Projects werden Shared Modules explizit in `#[NexumProject(modules: [...])]` genannt und private Modules aus ihrem Verzeichnis zugeordnet.

Der Build folgt `DISCOVER → CONFIGURE → VALIDATE → COMPILE`. Class-Strings sind Identitäten, Abhängigkeiten werden transitiv ergänzt und Scope-Verstöße oder Zyklen stoppen den Build. `nexum.modules.<id>.enabled: false` ist ein Compile-time-Override; es gibt keine Runtime-Mutation.

## Entity und Doctrine

`Nexum\\Entity` enthält nur die fachlich sichtbaren Entity-Kontrakte und Doctrine-gemappten Basisklassen. `Nexum\\Doctrine` enthält die Infrastruktur, die diese Fähigkeiten ausführt. Dadurch bleiben Entity-Modelle klein und Persistence-Verhalten austauschbar.

Standard ist `AbstractEntity` mit UUIDv7. Für ausdrücklich gewünschte Legacy-Fälle existiert `AbstractIntegerEntity`. Zeitstempel und Soft Delete werden durch weitere Basisklassen ergänzt:

```php
final class Invoice extends AbstractSoftDeletableEntity
{
}
```

Soft Delete besitzt bewusst kein zusätzliches `isDeleted`-Feld. Ausschließlich `deletedAt === null` oder ein gesetzter Zeitwert bestimmt den Zustand. Der Doctrine-Filter blendet gelöschte Datensätze bei normalen Abfragen aus. `restore()` setzt `deletedAt` wieder auf `null`.

## Persistenz

Der `EntityStoreInterface` bietet `persist`, `delete`, `restore`, `purge`, `flush` und `transactional`. `persist`, `delete` und `restore` führen absichtlich kein automatisches `flush()` aus. Damit bleiben Transaktionsgrenzen sichtbar und mehrere Änderungen können sicher gemeinsam gespeichert werden.

`delete()` verwendet Soft Delete bei passenden Entities und entfernt andere Entities physisch. `purge()` ist immer die explizite physische Löschung.

## Kernel

Ein normaler Symfony-Kernel plus `NexumBundle` unterstützt die vollständige Funktionalität. `NexumKernel` ist lediglich ein optionaler Shortcut und besitzt kein eigenes Nexum-Routing. Laufzeitinformationen liegen im separaten `KernelInformationInterface`; der Kernel wird nicht zum allgemeinen Servicecontainer.

`php bin/console nexum:about` zeigt Anwendung, Environment, Debug-Status, PHP-, Symfony- und Nexum-Version sowie Modulzahlen.

## Security und User

`Nexum\\Security` kennt keine konkrete Benutzer-Entity. Es definiert Access-Token-, Permission- und Security-Context-Verträge und besitzt einen neutralen Null-Adapter. `Nexum\\User` hängt in der erlaubten Richtung von Security ab und ergänzt Doctrine-Entities, User Provider, Token Store und Passwortverwaltung.

API-Tokens sind opaque und widerrufbar. Ihr Klartext wird nur beim Ausstellen zurückgegeben; gespeichert wird ausschließlich der SHA-256-Hash. Rollen bündeln positive Permissions, direkte Benutzer-Permissions ergänzen sie. Das API-Modul kennt nur den Permission-Namen und hängt nicht vom Security-Modul ab.

## Bewusste Grenzen

- Der generische REST-Controller akzeptiert ausschließlich ausdrücklich freigegebene Schreib-, Filter- und Sortierfelder. Komplexe fachliche Operationen bleiben eigene dünne Controller oder Services.
- Kein `isDeleted`-Duplikat.
- Keine gemappten Traits; die Doctrine-Vererbung bleibt nachvollziehbar.
- Keine Änderung oder Entfernung von Modulen zur Laufzeit. Management zeigt die kompilierte Architektur nur lesend an.
- Project-Aktivität filtert Routen und `ProjectContext::hasModule()`, aber nicht globale Services, Subscriber, Messenger Handler oder Commands.
- Projects sind Hostbereiche, keine automatische Tenant-, Daten- oder Token-Isolation.
- Webmail ist nicht Teil des Mailer-Moduls. Mailer bedeutet ausschließlich Versand; Mailpit dient nur der lokalen Entwicklung.
- MFA und Passkeys bleiben optional aktivierte Erweiterungsmodule und werden nicht in den Security-Kern gekoppelt.
