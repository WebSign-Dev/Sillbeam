# Nexum Foundation 0.1

## Ziel

Die Foundation soll den häufigen Backend-Code stark reduzieren, ohne versteckte Datenbankoperationen oder einen überladenen Kernel einzuführen. Die einfache Benutzung entsteht durch klar begrenzte Basisklassen, automatische Bundle-Konfiguration und wenige explizite Services.

## Projekt- und Modulstruktur

Nexum unterstützt parallel zwei Strukturen:

```text
src/Controller/...
src/Entity/...

src/Module/Billing/BillingModule.php
src/Module/Billing/Invoice/Controller/...
src/Module/Billing/Invoice/Entity/...
```

Klassische Controller werden weiterhin automatisch geladen. Modulare Controller werden nur geladen, wenn das übergeordnete Modul in `config/nexum/modules.yaml` aktiviert ist. Modulklassen erben von `AbstractModule` und deklarieren Metadaten mit `#[NexumModule(...)]`.

Abhängigkeiten zwischen Modulen werden beim Laden geprüft. Ein aktives Modul darf nicht von einem unbekannten oder deaktivierten Modul abhängen. Die YAML-Datei ist die einzige Quelle für den Aktivierungsstatus; dafür ist keine Datenbanktabelle nötig.

## Entity und Doctrine

`nexum/entity` enthält nur die fachlich sichtbaren Entity-Kontrakte und Doctrine-gemappten Basisklassen. `nexum/doctrine` enthält die Infrastruktur, die diese Fähigkeiten ausführt. Dadurch bleiben Entity-Modelle klein und Persistence-Verhalten austauschbar.

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

Die App erbt nur von `NexumKernel`. Der Kernel übernimmt die Symfony-Standardkonfiguration, klassisches Routing und aktivierungsabhängiges Modulrouting. Laufzeitinformationen liegen im separaten `KernelInformationInterface`; der Kernel selbst wird nicht zum allgemeinen Servicecontainer.

`php bin/console nexum:about` zeigt Anwendung, Environment, Debug-Status, PHP-, Symfony- und Nexum-Version sowie Modulzahlen.

## Security und User

`nexum/security` kennt keine konkrete Benutzer-Entity. Es definiert Access-Token-, Permission- und Security-Context-Verträge und besitzt einen neutralen Null-Adapter. `nexum/user` hängt in der erlaubten Richtung von Security ab und ergänzt Doctrine-Entities, User Provider, Token Store und Passwortverwaltung.

API-Tokens sind opaque und widerrufbar. Ihr Klartext wird nur beim Ausstellen zurückgegeben; gespeichert wird ausschließlich der SHA-256-Hash. Rollen bündeln positive Permissions, direkte Benutzer-Permissions ergänzen sie. Das API-Paket kennt nur den Permission-Namen und hängt nicht vom Nexum-Security-Paket ab.

## Bewusste Grenzen

- Der generische REST-Controller akzeptiert ausschließlich ausdrücklich freigegebene Schreib-, Filter- und Sortierfelder. Komplexe fachliche Operationen bleiben eigene dünne Controller oder Services.
- Kein `isDeleted`-Duplikat.
- Keine gemappten Traits; die Doctrine-Vererbung bleibt nachvollziehbar.
- Kein automatisches Entfernen von Modulen. Die spätere Management-Schicht muss Referenzen und Abhängigkeiten zuerst prüfen.
- Webmail ist nicht Teil des Mailer-Pakets. Mailer bedeutet ausschließlich Versand; Mailpit dient nur der lokalen Entwicklung.
- MFA und Passkeys bleiben optionale Erweiterungspakete und werden nicht in den Security-Kern gekoppelt.
