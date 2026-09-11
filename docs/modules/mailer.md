<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Mailer

`Nexum\Mailer` kapselt den Versand einfacher Text-/HTML-E-Mails hinter `MailerInterface` und verwendet standardmäßig Symfony Mailer.

## Aktivierung

```yaml
nexum:
    mailer:
        enabled: true
        default_from: no-reply@example.com
```

```php
$mailer->send(new EmailMessage(
    to: ['customer@example.com'],
    subject: 'Invoice ready',
    text: 'Your invoice is ready.',
    html: '<p>Your invoice is ready.</p>',
));
```

`EmailMessage` verlangt mindestens einen validen Empfänger, einen nichtleeren Subject ohne Zeilenumbrüche sowie Text oder HTML. Ein Sender am Message überschreibt `default_from`.

## Grenzen

- Keine Templates, Queueing-, Bounce- oder Newsletterlogik.
- Attachments und erweiterte MIME-Funktionen verwenden direkt Symfony Mailer oder einen eigenen Adapter.
- Mailpit ist Entwicklungsinfrastruktur, kein Teil dieser Komponente.

Weiter: [Infrastruktur-Guide](../guides/infrastructure.md) und [Messenger](messenger.md).
