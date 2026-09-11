<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Messenger

`Nexum\Messenger` stellt einen kleinen anwendungsneutralen Dispatch-Vertrag über Symfony Messenger bereit.

## Aktivierung

```yaml
nexum:
    messenger: { enabled: true }
```

```php
final readonly class IssueInvoice
{
    public function __construct(private MessageBusInterface $messages) {}

    public function __invoke(string $invoiceId): void
    {
        $this->messages->dispatch(new GenerateInvoicePdf($invoiceId));
    }
}
```

`SymfonyMessageBus` gibt das von Symfony erzeugte Envelope als `object` zurück. Transport, Routing, Retry-Strategien, Failure Transport und Workerbetrieb bleiben normale Symfony-Konfiguration.

## Project-Verhalten

Autokonfigurierte Handler verwendeter Module sind containerweit registriert. Project-Zuordnung filtert keine Messenger-Nachrichten oder Handler. Benötigt ein Handler Project-Kontext, muss die Nachricht eine ausdrückliche Project-ID tragen und der Handler sie über die Registry auflösen.

## Grenzen

- Kein zweiter Event- oder Message-Lifecycle.
- Keine automatische Mandanten-/Project-Isolation.
- Keine versteckten Transaktionen um Handler.

Weiter: [Infrastruktur-Guide](../guides/infrastructure.md) und [Projects](../guides/projects.md).
