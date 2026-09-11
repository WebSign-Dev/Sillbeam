# REST-Resources verwenden

Eine vollständige Standard-API benötigt nur eine Route, ein Resource-Attribut und die abstrakte Basisklasse:

```php
use App\Module\Billing\Invoice\Entity\Invoice;
use Nexum\Api\Attribute\ApiResource;
use Nexum\Api\Controller\AbstractResourceController;
use Nexum\Api\Scope\ResourceScope;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/billing/invoices', name: 'billing_invoice_')]
#[ApiResource(
    entity: Invoice::class,
    writableFields: ['number'],
    requiredFields: ['number'],
    filters: [
        'number' => ['eq', 'contains', 'in'],
        'createdAt' => ['gte', 'lte'],
    ],
    sortableFields: ['id', 'number', 'createdAt', 'updatedAt'],
    defaultSort: ['createdAt' => 'desc', 'id' => 'desc'],
    permissionPrefix: 'billing.invoice',
    scope: ResourceScope::SUBJECT,
    scopeField: 'owner',
)]
final class InvoiceController extends AbstractResourceController
{
}
```

Die Entity besitzt dafür eine nicht-nullbare Owner-Beziehung:

```php
use Nexum\Security\Contract\SubjectUserInterface;

#[ORM\ManyToOne(targetEntity: SubjectUserInterface::class)]
#[ORM\JoinColumn(name: 'owner_id', nullable: true, onDelete: 'CASCADE')]
private ?SubjectUserInterface $owner = null;
```

Nexum löst das Interface automatisch auf die in `nexum.user.entity_class` konfigurierte User-Entity auf.
`nullable: true` ist nur für die Übernahme alter, bislang eigentümerloser Datensätze nötig: Der API-Scope blendet sie sicher aus und setzt bei jedem neuen Datensatz einen Owner. In einem neuen Projekt ohne Legacy-Daten sollte die Beziehung `nullable: false` sein.

Damit entstehen automatisch:

```text
GET     /api/billing/invoices
GET     /api/billing/invoices/{id}
POST    /api/billing/invoices
PUT     /api/billing/invoices/{id}
PATCH   /api/billing/invoices/{id}
DELETE  /api/billing/invoices/{id}
```

Nicht benötigte Operationen können über `operations: [...]` eingeschränkt werden. Eine deaktivierte Operation liefert ein konsistentes `405 operation_not_allowed`.

## Response-Format

Erfolgreiche Antworten besitzen immer dieselbe Hülle:

```json
{
  "data": {},
  "meta": {},
  "errors": []
}
```

Fehler verwenden:

```json
{
  "data": null,
  "meta": {},
  "errors": [
    {
      "status": "422",
      "code": "validation_failed",
      "title": "Validation failed",
      "detail": "This value should not be blank.",
      "source": {"pointer": "number"}
    }
  ]
}
```

`DELETE` antwortet standardkonform ohne Body mit HTTP 204. `POST` liefert HTTP 201 und einen `Location`-Header.

## Seiten-Pagination

```text
GET /api/billing/invoices?page[number]=2&page[size]=25
```

Die Metadaten enthalten Modus, Seitennummer, Größe, Gesamtanzahl und Seitenanzahl. `page[size]` ist pro Resource begrenzt; Standard sind 20 und maximal 100.

## Cursor-Pagination

Die erste Cursor-Seite:

```text
GET /api/billing/invoices?page[mode]=cursor&page[size]=25
```

Weitere Seiten:

```text
GET /api/billing/invoices?page[after]=eyJpZCI6Ii4uLiJ9&page[size]=25
```

Cursor-Pagination sortiert stabil nach der Nexum-ID. Sie lässt sich bewusst nicht mit `sort` oder `page[number]` kombinieren. Dadurch bleibt die Abfrage eindeutig und für große Datenmengen effizient.

## Filter

Einfacher Gleichheitsfilter:

```text
filter[number]=NEX-1000
```

Explizite Operatoren:

```text
filter[number][contains]=NEX-
filter[number][in]=NEX-1000,NEX-1001
filter[createdAt][gte]=2026-01-01
filter[createdAt][lte]=2026-12-31
```

Vorhandene Operatoren sind `eq`, `neq`, `contains`, `gt`, `gte`, `lt`, `lte`, `in` und `is_null`. Ein Operator ist erst nutzbar, wenn er für das konkrete Feld im `ApiResource`-Attribut freigegeben wurde.

## Sortierung

```text
sort=number,-createdAt
```

Kein Minus bedeutet aufsteigend, ein Minus absteigend. Auch Sortierfelder müssen explizit freigegeben werden.

## Schreiben und Validierung

Schreibende Requests benötigen `Content-Type: application/json`. Felder außerhalb von `writableFields` werden abgelehnt. `requiredFields` gilt für das Erstellen, während `PUT` alle schreibbaren Felder verlangt. `PATCH` akzeptiert eine nicht leere Teilmenge.

Danach läuft die normale Symfony-Validator-Konfiguration der Entity. Ungültige Werte ergeben HTTP 422. Bereits vorhandene Unique-Werte ergeben HTTP 409.

## Berechtigungen

`permissionPrefix: 'billing.invoice'` aktiviert automatisch folgende Prüfungen:

```text
billing.invoice.read
billing.invoice.create
billing.invoice.update
billing.invoice.delete
```

Listen und einzelne Reads verwenden `.read`; PUT und PATCH verwenden `.update`. Ohne `permissionPrefix` führt die Resource selbst keine Permission-Prüfung aus. Ob die Route trotzdem eine Anmeldung verlangt, entscheidet die Symfony-Firewall. Rollen, direkte Benutzer-Permissions und Bearer-Authentifizierung sind in [security.md](security.md) beschrieben.

## Daten-Scope

Permissions beantworten, *was* ein Benutzer tun darf; der Scope begrenzt, *mit welchen Datensätzen*. Jede Resource ist standardmäßig `DENY` und muss ihre Grenze ausdrücklich wählen:

- `ResourceScope::SUBJECT` setzt beim Erstellen automatisch `scopeField` und ergänzt dieselbe Bedingung unvermeidbar bei Listen- und Einzelabfragen. Fremde IDs liefern 404.
- `ResourceScope::GLOBAL` ist nur für absichtlich globale Referenzdaten gedacht.
- Ohne explizite Wahl bleibt die Resource gesperrt.

`scopeField` kann eine Doctrine-Beziehung zum User oder ein String-/UUID-Feld mit der stabilen Subject-ID sein. Für echte Mandantenmodelle kann `ResourceScopeProviderInterface` einmal zentral durch einen Tenant-Provider ersetzt werden; Controller und Standardrouten bleiben unverändert.

## Fachliche Erweiterungen

Für kleine technische Ergänzungen können `beforeWrite()` und `afterWrite()` überschrieben werden. Größere Fachlogik gehört in injizierte Services. Eigene Endpunkte können von `AbstractApiController` erben und die Helfer `success()`, `created()`, `accepted()`, `noContent()`, `badRequest()`, `forbidden()`, `notFound()` und `validationError()` verwenden.
