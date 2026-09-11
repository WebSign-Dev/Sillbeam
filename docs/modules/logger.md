<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Logger

`Nexum\Logger` schützt strukturierte PSR-3-Kontexte vor häufigen Secret-Leaks.

## Verwendung

```php
$logger = new SafeLogger($psrLogger);
$logger->info('Login failed', [
    'user' => $email,
    'authorization' => $header,
]);
```

`SafeLogger` delegiert an einen vorhandenen `LoggerInterface`, nachdem `ContextSanitizer` den Kontext rekursiv verarbeitet hat.

## Redaction

Standardmäßig werden Schlüssel wie `authorization`, `cookie`, `password`, `secret`, `token`, `access_token`, `refresh_token` und `credential` zu `[REDACTED]`. Bindestriche und Punkte werden für den Vergleich normalisiert; auch Suffixe wie `request.access_token` werden erkannt.

Verschachtelung ist auf acht Ebenen und einzelne Strings auf 4096 Zeichen begrenzt. Eigene Schlüssel und Tiefe können über den Konstruktor konfiguriert werden.

## Grenzen

- Freitextnachrichten werden nicht semantisch bereinigt.
- Fachlich sensible Schlüssel müssen ergänzt werden.
- Redaction ersetzt keine sichere Logtransport- und Aufbewahrungspolitik.

Weiter: [Security-Checkliste](../operations/security-checklist.md).
