<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Notification

`Nexum\Notification` routet provider-neutrale Benachrichtigungen an benannte Channels.

```php
$notification = new Notification(
    type: 'invoice.ready',
    recipient: 'customer-42',
    subject: 'Invoice ready',
    body: 'Your invoice is available.',
    data: ['invoice_id' => '42'],
);

$router->send('email', $notification);
```

Ein `NotificationChannelInterface` liefert einen eindeutigen Namen und implementiert `send()`. Doppelte Channel-Namen erzeugen beim Aufbau des Routers einen Fehler; unbekannte Namen schlagen explizit fehl.

`Notification` verlangt Typ und Recipient, begrenzt den Body auf 1 MiB und erlaubt nur skalare Metadaten. E-Mail, Push, SMS oder In-App-Zustellung bleiben eigene Channel-Adapter. Retry und asynchrone Verarbeitung können über Messenger erfolgen.

Weiter: [Mailer](mailer.md), [Messenger](messenger.md) und [Optionale Bausteine](../guides/optional-modules.md).
