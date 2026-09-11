<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Health

`Nexum\Health` aggregiert unabhängige Health Checks, ohne selbst eine öffentliche Route zu erzwingen.

```php
final class DatabaseHealth implements HealthCheckInterface
{
    public function check(): HealthResult
    {
        return new HealthResult('database', HealthStatus::Healthy);
    }
}
```

`HealthRegistry::check()` liefert Gesamtstatus und Einzelergebnisse. Priorität: `unhealthy` vor `degraded` vor `healthy`. Wirft ein Check eine Exception, wird er fail-closed als `unhealthy` aufgenommen; Metadaten enthalten nur die Exception-Klasse.

`HealthResult` erlaubt ausschließlich skalare oder `null`-Metadaten. Secrets und vollständige Verbindungsfehler gehören nicht in öffentliche Health-Antworten.

Eine Anwendung entscheidet selbst über Route, Authentifizierung, Statuscode und Trennung von Liveness und Readiness.

Weiter: [Security-Checkliste](../operations/security-checklist.md).
