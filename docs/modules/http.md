<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# HTTP

`Nexum\Http` stellt einen begrenzten ClientContext und konsistente Request-IDs bereit. Es baut auf Symfony HttpFoundation und dem RequestStack auf.

## ClientContext

```php
$context = $resolver->resolve();

$context->ip;
$context->userAgent;
$context->locale;
$context->requestId;
```

`ClientContextResolverInterface::resolve()` verwendet optional einen expliziten Request oder den aktuellen RequestStack. Ohne Request bleiben IP, User Agent und Locale `null`; eine Request-ID wird dennoch erzeugt.

## Request-ID

Ein eingehender `X-Request-Id` wird nur übernommen, wenn er 1–64 Zeichen aus Buchstaben, Zahlen, `.`, `_` oder `-` enthält. Sonst erzeugt Nexum eine UUIDv7. `RequestIdSubscriber` schreibt die aufgelöste ID als Response-Header zurück.

## Begrenzungen

- User Agent maximal 512 Zeichen
- Preferred Language maximal 32 Zeichen
- IP-Auflösung folgt Symfonys Trusted-Proxy-Konfiguration
- Headerwerte werden nicht ungeprüft als Trace- oder Security-ID vertraut

## Project-Bezug

Die Komponente kennt keine Projects. Hostauflösung liegt im ProjectContext; HTTP-Metadaten bleiben ein unabhängiger Infrastrukturbaustein.

Weiter: [Client](client.md), [Projects](../guides/projects.md) und [Security-Checkliste](../operations/security-checklist.md).
