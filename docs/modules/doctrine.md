<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Doctrine

`Nexum\Doctrine` verbindet Nexum-Entities mit Doctrine ORM. Die Integration registriert Entity-Mappings, Zeitstempel-Listener, den Soft-Delete-Filter und einen expliziten Entity Store.

## Aktivierung

```yaml
nexum:
    doctrine: { enabled: true }
```

DoctrineBundle muss registriert sein. Bei `enabled: false` bleiben reine Entity-Klassen verwendbar, Nexum registriert jedoch keine Mappings, Listener, Filter oder Store-Services.

## Automatische Mappings

Discovery sammelt `Entity/`-Verzeichnisse aus verwendeten Shared Modules, project-privaten Modules, Project-Bereichen und aktivierten User-, MFA- und Passkey-Komponenten. Mappings sind global; Projects erzeugen keine getrennten EntityManager oder Datenbanken.

## EntityStore

```php
public function __construct(private EntityStoreInterface $store)
{
}

public function create(Invoice $invoice): void
{
    $this->store->persist($invoice);
    $this->store->flush();
}
```

| Methode | Wirkung |
| --- | --- |
| `persist()` | Entity vormerken |
| `delete()` | Soft Delete, wenn unterstützt; sonst Remove |
| `restore()` | Soft-Delete-Zeitpunkt entfernen |
| `purge()` | immer physisch löschen |
| `flush()` | Unit of Work ausdrücklich schreiben |
| `transactional()` | Callback in Doctrine-Transaktion ausführen |

`persist()`, `delete()` und `restore()` führen absichtlich kein automatisches Flush aus.

## Filter und Listener

Der Filter `nexum_soft_delete` blendet Datensätze mit gesetztem `deletedAt` aus normalen Abfragen aus. `TimestampListener` reagiert auf Doctrine Lifecycle Events und verwendet eine PSR-Clock für reproduzierbare Tests.

## Grenzen

- Keine automatische Datenbank- oder Tenant-Trennung pro Project.
- Keine automatische Migrationserzeugung.
- Kein implizites Flush.
- `purge()` ist destruktiv.
- Fachliche Repositories bleiben Aufgabe der Anwendung.

Weiter: [Entity](entity.md), [Entities verwenden](../guides/entities.md) und [Konfiguration](../reference/configuration.md#integrationsschalter).
