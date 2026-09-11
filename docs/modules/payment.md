<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Payment

`Nexum\Payment` definiert provider-neutrale Zahlungswerte und einen Charge-Vertrag. Die Klassen sind reine PHP-Bausteine ohne Kernel- oder Project-Abhängigkeit.

```php
$request = new PaymentRequest(
    idempotencyKey: 'invoice:0194f6d5-1234',
    amount: new Money(1299, 'EUR'),
    reference: 'INV-2026-0042',
    metadata: ['customer_id' => '42'],
);

$result = $provider->charge($request);
```

`Money` verwendet nichtnegative Minor Units und einen dreistelligen Uppercase-Währungscode. 12,99 EUR werden als `1299` gespeichert; Rundungsfehler von Floats werden vermieden.

`PaymentRequest` verlangt einen 16–128 Zeichen langen Idempotency Key aus sicheren Zeichen und eine nichtleere Referenz. `PaymentResult` transportiert Provider-ID und Status.

`PaymentProviderInterface` sagt nicht, wie Refunds, Webhooks oder Capture funktionieren. Stripe, Adyen oder andere Anbieter bleiben eigene Adapter mit ihrem jeweiligen Zustandsmodell.

Weiter: [Optionale Bausteine](../guides/optional-modules.md).
