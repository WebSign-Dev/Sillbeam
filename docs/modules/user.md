<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# User

`Nexum\User` verbindet eine anwendungseigene User-Entity mit Doctrine und Symfony Security. Die Anwendung bleibt Eigentümerin ihrer konkreten Klasse und kann `AbstractUser` fachlich erweitern.

## Aktivierung

```yaml
nexum:
    doctrine: { enabled: true }
    security: { enabled: true }
    user:
        enabled: true
        entity_class: App\Entity\User
```

User benötigt Doctrine und Security. Nexum registriert ein Resolve-Target für `SubjectUserInterface`, den Doctrine User Provider, Passwortverwaltung und den Doctrine AccessTokenStore.

## Eigene User-Entity

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexum\User\Entity\AbstractUser;

#[ORM\Entity]
final class User extends AbstractUser
{
}
```

`AbstractUser` enthält:

- normalisierte, eindeutige E-Mail als Login-Identifier;
- UUIDv7 als stabile Subject-ID;
- Passwort-Hash mit Serializer-`Ignore`;
- Rollen plus garantiertes `ROLE_USER`;
- direkte Permissions;
- Aktivstatus;
- Zeitstempel und Soft Delete.

## Rollen und Permissions

```php
$user->setRoles(['ROLE_BILLING_MANAGER']);
$user->setPermissions(['billing.invoice.read']);
$user->deactivate();
```

Rollen müssen `ROLE_NAME` entsprechen. Permissions sind `*` oder dotted lowercase names. Getter deduplizieren und sortieren die Werte.

## Passwortverwaltung

```php
$hash = $passwordManager->hash($user, $plainPassword);
$user->setPasswordHash($hash);
```

Das konkrete Hashing steuert Symfony über `password_hashers`. Klartextpasswörter werden als SensitiveParameter behandelt und nicht in der Entity gespeichert.

## Token Store

`ApiAccessToken` speichert Hash, Label, Ausgabe-/Ablaufzeitpunkt, Widerruf und User-Beziehung. Ein bestehendes Token bleibt über UUID an dasselbe Subject gebunden, auch wenn dessen E-Mail geändert und später wiederverwendet wird.

## CLI

```bash
php bin/console nexum:user:create user@example.com \
  --role=ROLE_USER \
  --permission=catalog.product.read
```

## Grenzen

- Keine Registrierung, Passwort-Reset- oder E-Mail-Verifikation als versteckte Standardroute.
- Keine Annahme, dass E-Mail eine dauerhafte Subject-ID ist.
- Deaktivierung und Soft Delete sind getrennte Zustände.
- Das konkrete User-Domainmodell bleibt in der Anwendung.

Weiter: [Security](security.md), [Entity](entity.md) und [CLI](../reference/cli.md).
