<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# QR Code

`Nexum\QrCode` erzeugt QR-Codes als SVG hinter `QrCodeGeneratorInterface`.

## Aktivierung und Verwendung

```yaml
nexum:
    qrcode: { enabled: true }
```

```php
$svg = $qrCodes->svg('otpauth://totp/Nexum:user@example.com?...', 320);
```

`EndroidQrCodeGenerator` akzeptiert 1–4096 Datenbytes und Größen von 64–2048 Pixeln. Der Renderer verwendet zehn Pixel Margin und liefert den SVG-Inhalt als String.

Die Komponente entscheidet nicht, ob Daten sicher oder öffentlich sind. TOTP-Secrets und Login-Tokens dürfen nur im vorgesehenen, authentifizierten Flow gerendert werden.

Weiter: [MFA](security-mfa.md) und [Infrastruktur-Guide](../guides/infrastructure.md).
