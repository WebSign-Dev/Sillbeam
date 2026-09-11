<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Exception

`Nexum\Exception` liefert eine kleine Hierarchie für Fehlergrenzen außerhalb des API-Problemformats.

```text
RuntimeException
└── NexumException implements NexumExceptionInterface
    ├── DomainException
    │   └── NotFoundException
    └── InfrastructureException
```

`DomainException` beschreibt fachlich ungültige Operationen. `InfrastructureException` kapselt technische Ausfälle. `NotFoundException` erzeugt eine konsistente Meldung aus Ressourcentyp und Identifier.

```php
throw new NotFoundException('Invoice', $invoiceId);
```

Die Klassen bestimmen nicht automatisch einen HTTP-Status. An API-Grenzen werden Fehler bewusst in `ApiProblemException` oder eine anwendungseigene Darstellung übersetzt.

Weiter: [API](api.md) und [Core](core.md).
