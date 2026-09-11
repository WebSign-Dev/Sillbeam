<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Security

`Nexum\Security` liefert opaque Access Tokens, Permission-Auflösung, einen SecurityContext und HTTP-Handler für konsistente API-Fehler. Es kennt keine konkrete User-Entity; diese Kopplung übernimmt optional `Nexum\User`.

## Fähigkeit und API getrennt aktivieren

```yaml
nexum:
    security:
        enabled: true
        access_token_ttl: 3600
        api:
            enabled: false
            projects: []
```

`security.enabled` registriert Services. Erst `security.api.enabled` exponiert Tokenrouten. Im Project Mode ist zusätzlich eine Project-Allowlist oder ausdrücklich `['*']` erforderlich.

## Contracts

| API | Verantwortung |
| --- | --- |
| `SubjectUserInterface` | stabile Subject-ID zusätzlich zur Login-ID |
| `PermissionAwareUserInterface` | direkte Permissions bereitstellen |
| `ActiveUserInterface` | aktive/deaktivierte Accounts markieren |
| `SecurityContextInterface` | User, Identifier, Permissions und `isGranted()` lesen |
| `AccessTokenStoreInterface` | gehashte Tokens speichern, finden und widerrufen |
| `SecondFactorGateInterface` | optional MFA vor Tokenausgabe verlangen |

Null-Implementierungen halten Security ohne User/MFA nutzbar; aktivierte Integrationen ersetzen sie über DI.

## Token-Lifecycle

1. Symfony `json_login` authentifiziert Identifier und Passwort.
2. `TokenAuthenticationSuccessHandler` prüft den Second-Factor-Gate.
3. `AccessTokenManager` erzeugt 32 zufällige Bytes und den Prefix `nxm_`.
4. Nur der SHA-256-Hash wird über den Store gespeichert.
5. Der Klartext wird einmalig an den Client zurückgegeben.
6. `AccessTokenHandler` validiert eingehende Bearer Tokens.
7. Widerruf betrifft ein Token oder alle Tokens eines Subjects.

```php
$issued = $tokens->issue($user, 'Mobile device');
$tokens->revoke($issued->plainToken);
$tokens->revokeAllForUser($user);
```

## Permissions

Permissions verwenden dotted lowercase names:

```text
billing.invoice.read
billing.invoice.create
```

Rollen können positive Permissions bündeln:

```yaml
nexum:
    security:
        role_permissions:
            ROLE_BILLING_MANAGER:
                - billing.invoice.read
                - billing.invoice.create
```

Direkte User-Permissions ergänzen Rollen. `PermissionVoter` integriert die Entscheidung in Symfonys Authorization-System.

## HTTP-Verhalten

Authentication Entry Point, Failure Handler und Access-Denied Handler liefern strukturierte JSON-Probleme. Token- und MFA-Endpunkte sollten über Symfony `requires_channel: https` geschützt werden. Login Throttling bleibt normale Symfony-Konfiguration.

## Grenzen

- Keine automatische Project-, Tenant- oder Token-Isolation.
- Kein Klartext-Token-Recovery.
- Project-Routing ersetzt keine Authorization.
- API-Routen sind standardmäßig nicht öffentlich.
- MFA und Passkeys sind eigenständig aktivierte Erweiterungen.

Weiter: [Security-Guide](../guides/security.md), [User](user.md), [MFA](security-mfa.md), [Passkeys](security-passkey.md) und [Produktionscheckliste](../operations/security-checklist.md).
