<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Entity

`Nexum\Entity` enthält Doctrine-kompatible Basisklassen und kleine Contracts. Die Klassen tragen ausschließlich Entity-Zustand; Persistenz, Listener und Filter liegen in `Nexum\Doctrine`.

## Basisklassen

| Klasse | Enthält | Geeignet für |
| --- | --- | --- |
| `AbstractEntity` | UUIDv7 `id` | Standard für neue Entities |
| `AbstractIntegerEntity` | Integer-ID | bewusste Legacy-Modelle |
| `AbstractTimestampedEntity` | UUIDv7, `createdAt`, `updatedAt` | automatisch zeitgestempelte Entities |
| `AbstractSoftDeletableEntity` | zusätzlich `deletedAt` | wiederherstellbare Datensätze |

## Beispiel

```php
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\AbstractSoftDeletableEntity;

#[ORM\Entity]
final class Invoice extends AbstractSoftDeletableEntity
{
    #[ORM\Column(length: 40)]
    private string $number;
}
```

Die UUID wird bereits im Konstruktor erzeugt. Das Objekt besitzt daher vor dem ersten Flush eine stabile Identität.

## Contracts

- `EntityInterface::getId()` beschreibt die Nexum-Entity-Identität.
- `TimestampedInterface` stellt Zeitstempelzugriff und Listener-Methoden bereit.
- `SoftDeletableInterface` beschreibt `deletedAt`, Markierung und Wiederherstellung.

Anwendungscode sollte `markDeleted()` normalerweise nicht direkt aufrufen, sondern `EntityStoreInterface::delete()` verwenden.

## Zeitstempel und Soft Delete

`createdAt` und `updatedAt` starten als `null`. Der Doctrine-Listener initialisiert sie mit einer injizierten Clock. `deletedAt === null` bedeutet aktiv; `restore()` setzt den Zustand zurück. Ein zusätzliches `isDeleted`-Feld existiert bewusst nicht.

## Nutzung ohne Kernel

Die Basisklassen lassen sich ohne gestarteten Symfony-Kernel instanziieren. Erst automatische Zeitstempel, Filter und `EntityStore` benötigen die Doctrine-Integration.

## Grenzen

- Keine gemappten Traits.
- Keine versteckten Repository- oder Registry-Zugriffe.
- Keine Abhängigkeit auf Module, Project oder Routing.
- Soft Delete ist keine revisionssichere Historie.
- Integer-IDs sind kein Standard für neue Modelle.

Weiter: [Entities verwenden](../guides/entities.md) und [Doctrine](doctrine.md).
