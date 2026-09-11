<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Serializer

`Nexum\Serializer` bietet einen kleinen JSON-Vertrag über Symfonys Serializer. Er eignet sich für Services, die nicht direkt von der großen Symfony-Schnittstelle abhängen sollen.

## API

```php
$json = $serializer->json($invoice, ['groups' => ['invoice:read']]);
$command = $serializer->fromJson($payload, CreateInvoice::class);
```

`SerializerInterface` definiert `json()` und `fromJson()`. `SymfonySerializer` delegiert an Symfony und übernimmt dessen Normalizer-, Encoder- und Context-System.

## Sicherheitsdefaults

- Eingehendes JSON ist auf 1 MiB begrenzt.
- Zirkuläre Objektgraphen werden nicht still gekürzt, sondern als Fehler gemeldet.
- Zusätzlicher Symfony-Kontext kann pro Aufruf angegeben werden.
- Sensitive Felder müssen weiterhin über Serializer-Attribute oder Gruppen ausgeschlossen werden.

## Abgrenzung

Die API-Komponente verwendet eigene Response- und Payload-Mapping-Dienste. Dieser Serializer ist der allgemeinere Anwendungsadapter und kein Ersatz für fachliche DTO-Validierung.

Weiter: [API](api.md) und [öffentliche API](../reference/public-api.md).
