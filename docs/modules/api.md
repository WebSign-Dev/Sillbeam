<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# API

`Nexum\Api` stellt deklarative REST-Ressourcen auf Basis von Symfony Routing, Serializer, Validator und Doctrine bereit. Es ist für standardisierte CRUD-Oberflächen gedacht; fachliche Workflows bleiben eigene Controller und Application Services.

## Aktivierung

```yaml
nexum:
    doctrine: { enabled: true }
    api: { enabled: true }
```

Die API-Integration benötigt Doctrine. `api.enabled` veröffentlicht allein keine Auth-, MFA-, Passkey- oder Management-Routen.

## Minimale Resource

```php
use App\Entity\Product;
use Nexum\Api\Attribute\ApiResource;
use Nexum\Api\Controller\AbstractResourceController;
use Nexum\Api\Scope\ResourceScope;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'product_')]
#[ApiResource(
    entity: Product::class,
    writableFields: ['name', 'price'],
    requiredFields: ['name'],
    scope: ResourceScope::GLOBAL,
)]
final class ProductController extends AbstractResourceController
{
}
```

Ohne eingeschränkte `operations` entstehen Listen, Lesen, Erstellen, Ersetzen, Aktualisieren und Löschen.

## `ApiResource`-Optionen

| Option | Bedeutung | Standard |
| --- | --- | --- |
| `entity` | Entity-Class-String | erforderlich |
| `operations` | erlaubte `Operation`-Enums | alle Operationen |
| `writableFields` | Request-Allowlist | leer |
| `requiredFields` | Pflichtfelder beim Erstellen | leer |
| `filters` | Feld → erlaubte Operatoren | leer |
| `sortableFields` | Sortier-Allowlist | `['id']` |
| `defaultSort` | stabile Standardsortierung | `['id' => 'asc']` |
| `defaultPageSize` | Seitengröße | `20` |
| `maxPageSize` | Obergrenze | `100` |
| `permissionPrefix` | Basisname für CRUD-Permissions | `null` |
| `scope` | `DENY`, `GLOBAL` oder `SUBJECT` | `DENY` |
| `scopeField` | Owner-/Subject-Feld | `null` |
| `normalizationContext` | Symfony-Serializer-Kontext | leer |
| `denormalizationContext` | Symfony-Serializer-Kontext | leer |

Der sichere Scope-Standard ist `DENY`: Eine Resource muss ihren Datenzugriff ausdrücklich festlegen.

## Operationen und Permissions

| Operation | HTTP | Permission-Suffix |
| --- | --- | --- |
| `INDEX` | GET Collection | `.read` |
| `READ` | GET Item | `.read` |
| `CREATE` | POST | `.create` |
| `REPLACE` | PUT | `.update` |
| `UPDATE` | PATCH | `.update` |
| `DELETE` | DELETE | `.delete` |

Bei `permissionPrefix: 'catalog.product'` prüft Nexum zum Beispiel `catalog.product.read`. Ohne Prefix übernimmt die Resource keine eigene Permission-Prüfung; die Symfony-Firewall kann weiterhin Authentifizierung verlangen.

## Abfragen

```text
GET /api/products?page[number]=2&page[size]=25
GET /api/products?page[mode]=cursor&page[size]=25
GET /api/products?page[after]=<cursor>&page[size]=25
GET /api/products?filter[name][contains]=book&sort=-createdAt,name
```

Unterstützte Filteroperatoren sind `eq`, `neq`, `contains`, `gt`, `gte`, `lt`, `lte`, `in` und `is_null`; jeder Operator muss pro Feld freigegeben sein.

## Antwortformat

```json
{"data": {}, "meta": {}, "errors": []}
```

Fehler verwenden dieselbe Hülle mit `data: null` und strukturierten `errors`. `ApiExceptionSubscriber` normalisiert bekannte API-Probleme. POST liefert 201 und `Location`; DELETE liefert 204 ohne Body.

## Erweiterungspunkte

- `AbstractApiController` für eigene Endpunkte
- `ResourceScopeProviderInterface` für eigene Daten-/Tenant-Scopes
- `ApiResponseFactoryInterface` für Response-Erzeugung
- `beforeWrite()` und `afterWrite()` für kleine technische Hooks
- Symfony Serializer Contexts für Gruppen und Normalisierung

Größere Fachlogik gehört in injizierte Services und explizite Endpunkte.

## Grenzen

- Nur JSON-Schreibrequests werden akzeptiert.
- Felder außerhalb der Allowlist werden abgelehnt.
- Cursorpagination lässt sich nicht mit freier Sortierung kombinieren.
- Projects ersetzen keinen Daten-Scope.
- API-Ressourcen verstecken keine Transaktionsgrenzen.

Ausführlicher Ablauf: [REST-Resources verwenden](../guides/api.md). Siehe außerdem [Entity](entity.md), [Doctrine](doctrine.md) und [Security](security.md).
