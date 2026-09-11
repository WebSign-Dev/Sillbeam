<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Entities verwenden

Für neue Entities ist UUIDv7 der Standard:

```php
#[ORM\Entity]
final class Invoice extends AbstractSoftDeletableEntity
{
}
```

`AbstractSoftDeletableEntity` bringt `id`, `createdAt`, `updatedAt` und `deletedAt` mit. Doctrine setzt die Zeitstempel über eine injizierte Clock. Das erleichtert spätere deterministische Tests.

Speichern und löschen bleibt explizit:

```php
$store->persist($invoice);
$store->flush();

$store->delete($invoice);
$store->flush();

$store->restore($invoice);
$store->flush();
```

Normale Doctrine-Abfragen sehen soft-gelöschte Entities nicht. Für administrative Wiederherstellung kann der Filter `nexum_soft_delete` gezielt deaktiviert und anschließend wieder aktiviert werden.
