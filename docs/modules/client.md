<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Client

`Nexum\Client` projiziert den HTTP-ClientContext auf das einfache readonly Model `Client`.

```php
public function __construct(private ClientProvider $clients) {}

$client = $this->clients->current();
$client->ip;
$client->userAgent;
$client->locale;
$client->requestId;
```

`ClientProvider` delegiert vollständig an `ClientContextResolverInterface`. Dadurch bleiben Trusted-Proxy-Regeln, Headerbegrenzungen und Request-ID-Erzeugung zentral in `Nexum\Http`.

Die Daten sind technische Request-Metadaten, keine authentifizierte Identität. IP, User Agent und Locale dürfen nicht als alleinige Autorisierungsgrundlage verwendet werden.

Weiter: [HTTP](http.md) und [Security](security.md).
