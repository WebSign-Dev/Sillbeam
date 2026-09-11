<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Konfigurationsreferenz

Die gesamte Framework-Konfiguration liegt unter `nexum:`. Konventionen liefern den Standard; Konfiguration überschreibt nur Entscheidungen, die Nexum nicht sicher ableiten kann.

## Vollständiges Beispiel

```yaml
nexum:
    discovery:
        root: '%kernel.project_dir%/src'
        # namespace: 'App\'

    modules:
        legacy:
            enabled: false
        'project:admin:old-reports':
            enabled: false

    projects:
        storefront:
            hosts: [shop.example.com, www.example.com]
        admin:
            hosts: [admin.example.com]

    doctrine:
        enabled: true

    api:
        enabled: true

    user:
        enabled: true
        entity_class: App\Entity\User

    filesystem:
        enabled: true
        root: '%kernel.project_dir%/storage/files'
        max_read_bytes: 10485760

    mailer:
        enabled: true
        default_from: no-reply@example.com

    messenger:
        enabled: true

    qrcode:
        enabled: true

    security:
        enabled: true
        access_token_ttl: 3600
        role_permissions:
            ROLE_BILLING_MANAGER:
                - billing.invoice.read
                - billing.invoice.create
        api:
            enabled: true
            projects: [api]
        mfa:
            enabled: true
            issuer: Nexum
            challenge_ttl: 300
            max_attempts: 5
            api:
                enabled: true
                projects: [api]
        passkeys:
            enabled: true
            challenge_ttl: 300
            max_attempts: 5
            relying_parties:
                storefront:
                    rp_id: example.com
                    allowed_origins: ['https://shop.example.com']
                admin:
                    rp_id: admin.example.com
                    allowed_origins: ['https://admin.example.com']
            api:
                enabled: true
                projects: [storefront, admin]

    management:
        enabled: true
        api:
            enabled: false
            projects: []
```

## `discovery`

| Option | Standard | Bedeutung |
| --- | --- | --- |
| `root` | `%kernel.project_dir%/src` | Wurzel für Module- und Project-Discovery |
| `namespace` | automatisch | PSR-4-Namespace für den Root, nur bei Mehrdeutigkeit nötig |

Nexum liest das Composer-PSR-4-Mapping, das den Discovery-Root am spezifischsten abdeckt. Zusätzliche unabhängige Mappings sind erlaubt. Ein Override betrifft ausschließlich Discovery und ersetzt nicht Composer-Autoloading.

## `modules`

`enabled: false` deaktiviert ein gefundenes Modul zur Compile-Zeit. Shared Modules können über ihre ID oder den expliziten Schlüssel angesprochen werden:

```yaml
nexum:
    modules:
        billing: { enabled: false }
        'shared:catalog': { enabled: false }
        'project:admin:checkout': { enabled: false }
```

Benötigt ein aktiver Graph ein deaktiviertes Modul, schlägt der Build fehl. Nexum deaktiviert nicht heimlich abhängige Module mit.

## `projects`

Nur Hosts werden konfiguriert. Klasse, Namespace, Verzeichnis, Controller, Entities und Module stammen aus Discovery und Descriptoren.

- Ein Project: Hosts optional.
- Mehrere Projects: mindestens ein exakter Host pro Project.
- Der erste Host ist canonical.
- Keine Wildcards, Ports, Schemes oder Pfade.
- Derselbe Host darf nicht mehreren Projects gehören.

Symfonys `trusted_hosts` bleibt eine zusätzliche, unabhängige Sicherheitsgrenze.

## Integrationsschalter

| Integration | Standard | Voraussetzung/Wirkung |
| --- | --- | --- |
| `doctrine.enabled` | `true` | Doctrine-Mappings, Filter, Listener und EntityStore |
| `api.enabled` | `true` | generische REST-Infrastruktur; benötigt Doctrine |
| `user.enabled` | `false` | User-Entity-Integration; benötigt Doctrine und Security |
| `filesystem.enabled` | `false` | lokaler Storage-Service |
| `mailer.enabled` | `false` | Adapter für Symfony Mailer |
| `messenger.enabled` | `false` | Adapter für Symfony Messenger |
| `qrcode.enabled` | `false` | SVG-QR-Code-Generator |
| `security.enabled` | `true` | Token- und Permission-Services, nicht automatisch deren API |
| `management.enabled` | `false` | read-only Architekturansicht |

Deaktivierte Integrationen registrieren ihre abhängigen Services, Listener, Rate Limiter und APIs nicht. Composer-Abhängigkeiten bleiben Bestandteil des Einzelpackages.

## Security

`access_token_ttl` beträgt standardmäßig 3600 Sekunden und muss mindestens 60 betragen. `role_permissions` bildet Symfony-Rollen auf positive Nexum-Permissions ab. Direkte Benutzer-Permissions können diese ergänzen.

Der Schalter `security.enabled` aktiviert die Fähigkeit, aber nicht die öffentlichen Tokenrouten. Diese benötigen separat `security.api.enabled: true`.

## MFA

| Option | Standard | Grenze |
| --- | --- | --- |
| `issuer` | `Nexum` | Anzeigename im Authenticator |
| `challenge_ttl` | `300` | 60–900 Sekunden |
| `max_attempts` | `5` | 1–10 Versuche |

MFA benötigt Security und Doctrine. Die MFA-API bleibt ein separater Expositionsschalter.

## Passkeys

Im Application Mode wird eine globale Relying Party verwendet:

```yaml
nexum:
    security:
        passkeys:
            enabled: true
            relying_party:
                rp_id: app.example.com
                allowed_origins: ['https://app.example.com']
```

Im Project Mode werden Relying Parties nach Project-ID konfiguriert. Jedes für die Passkey-API freigeschaltete Project benötigt eine passende RP-Konfiguration.

## API-Exposition im Project Mode

Jede exponierende API besitzt `enabled` und `projects`:

```yaml
api:
    enabled: true
    projects: [admin]
```

Eine leere oder fehlende Project-Liste ist bei aktivierter API ein Build-Fehler. Alle Projects müssen ausdrücklich mit `projects: ['*']` freigeschaltet werden. Im Application Mode entfällt die Liste.

Betroffen sind:

- `security.api`
- `security.mfa.api`
- `security.passkeys.api`
- `management.api`

Siehe auch [Security](../guides/security.md), [MFA](../guides/mfa.md) und [Passkeys](../guides/passkeys.md).
