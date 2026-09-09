# Security verwenden

`nexum/security` stellt Authentifizierung, Token-Lebenszyklus, Permission-Auflösung und den Security Context bereit. `nexum/user` ist der optionale Doctrine-Adapter mit einer erweiterbaren Benutzerbasis. Dadurch kann Security auch mit einem eigenen User Provider oder Token Store verwendet werden.

## Benutzer-Entity

Die Anwendung benötigt nur eine konkrete Doctrine-Entity:

```php
use Doctrine\ORM\Mapping as ORM;
use Nexum\User\Entity\AbstractUser;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_user')]
final class User extends AbstractUser
{
}
```

Die Klasse enthält UUIDv7, E-Mail, Passwort-Hash, Rollen, direkte Permissions, `active`, Zeitstempel und `deletedAt`. Der Passwort-Hash ist von der Symfony-Serialisierung ausgeschlossen. Profilfelder wie Name, Adresse oder Telefonnummer bleiben bewusst anwendungsspezifisch.

Die konkrete Klasse wird einmal angegeben:

```yaml
nexum_user:
    entity_class: App\Module\Identity\User\Entity\User
```

## Rollen und Permissions

Permissions werden positiv vergeben. Ohne Freigabe ist eine Operation nicht erlaubt. Sie können direkt am Benutzer gespeichert oder einer Rolle zugeordnet werden:

```yaml
nexum_security:
    access_token_ttl: 3600
    role_permissions:
        ROLE_BILLING_MANAGER:
            - billing.invoice.read
            - billing.invoice.create
            - billing.invoice.update
            - billing.invoice.delete
```

Die globale Permission `*` ist für bewusst eingerichtete technische Administratoren verfügbar. Namespace-Wildcards wie `billing.*` werden absichtlich nicht interpretiert, damit eine neue Permission nicht unbemerkt freigegeben wird.

Eine API-Resource aktiviert die Prüfung mit:

```php
#[ApiResource(
    entity: Invoice::class,
    permissionPrefix: 'billing.invoice',
)]
```

Listen und Lesen prüfen `.read`, Erstellen `.create`, PUT und PATCH `.update`, Löschen `.delete`.

## Benutzer anlegen

Der Konsolenbefehl fragt das Passwort verdeckt ab und speichert nur den von Symfony erzeugten Hash:

```bash
php bin/console nexum:user:create admin@example.test \
    --role ROLE_BILLING_MANAGER
```

Für kontrollierte Automatisierung kann das Passwort mit `--password-stdin` über die Standardeingabe übergeben werden. Ein Passwort als sichtbares Kommandozeilenargument wird nicht angeboten.

Im Anwendungscode kapselt `PasswordManagerInterface` das Symfony-Hashing:

```php
$user->setPasswordHash($passwords->hash($user, $plainPassword));
```

## API-Login

```http
POST /api/auth/token
Content-Type: application/json

{
  "identifier": "admin@example.test",
  "password": "...",
  "label": "Browser"
}
```

Die erfolgreiche Antwort enthält den einzigen Klartextwert des Tokens:

```json
{
  "data": {
    "access_token": "nxm_...",
    "token_type": "Bearer",
    "expires_at": "2026-09-06T13:00:00+00:00"
  },
  "meta": {},
  "errors": []
}
```

Weitere Requests senden ausschließlich:

```http
Authorization: Bearer nxm_...
```

`GET /api/auth/me` liefert den veränderlichen Login-Identifier, die stabile Subject-UUID, Rollen und aufgelöste Permissions. `DELETE /api/auth/token` widerruft das aktuelle Token unmittelbar.

## Sicherheitsmodell

- Tokenmaterial wird mit `random_bytes(32)` erzeugt.
- In der Datenbank liegt ausschließlich ein SHA-256-Hash des hochentropischen Tokens.
- Tokens, MFA-Enrollments und Passkeys referenzieren die unveränderliche User-UUID über echte Foreign Keys. Eine Änderung oder spätere Wiederverwendung der E-Mail kann deshalb keine Credentials auf ein anderes Konto übertragen.
- Tokens besitzen eine feste Ablaufzeit und können serverseitig widerrufen werden.
- Zugangsdaten und Tokens werden nicht als URL-Parameter unterstützt.
- Loginfehler unterscheiden extern nicht zwischen unbekanntem Benutzer, falschem Passwort und deaktiviertem Konto.
- Symfony Login Throttling begrenzt fehlgeschlagene Anmeldungen.
- Ungeschützte API-Routen müssen vor der allgemeinen `^/api`-Regel ausdrücklich als `PUBLIC_ACCESS` eingetragen werden.
- Alle API-Regeln erzwingen HTTPS. Fabryq Harbor stellt lokal ein vertrauenswürdiges mkcert-Zertifikat bereit.
- `TRUSTED_PROXIES` enthält nur bekannte Reverse Proxies; `REMOTE_ADDR` ist ausdrücklich kein Vertrauensanker. `X-Forwarded-Host` wird nicht ausgewertet und `APP_TRUSTED_HOSTS` begrenzt erlaubte Hosts.

Für Produktion müssen `TRUSTED_PROXIES` und `APP_TRUSTED_HOSTS` auf die realen Proxy-Netze und Domains gesetzt werden. `private_ranges` wird ausschließlich von der Harbor-Development-Konfiguration verwendet.

Die Implementierung verwendet Symfonys offiziellen Access-Token-Authenticator und hält sich beim Transport an die Empfehlungen, Bearer-Tokens im Authorization-Header zu senden: [Symfony Access Token Authentication](https://symfony.com/doc/7.4/security/access_token.html), [OWASP REST Security](https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html).

## Optionale Erweiterungen

MFA und Passkeys bleiben eigene Pakete und ergänzen denselben Benutzer- und Token-Lebenszyklus. Anwendungen ohne diese Verfahren installieren die Pakete einfach nicht. Die vollständigen Abläufe stehen in [MFA](mfa.md) und [Passkeys](passkeys.md).
