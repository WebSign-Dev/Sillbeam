<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Validator

`Nexum\Validator` ergänzt Symfony Validator um wiederverwendbare Constraints. Aktuell gehört `#[StrongPassword]` zur öffentlichen API.

## Verwendung

```php
use Nexum\Validator\Constraint\StrongPassword;

final readonly class RegistrationInput
{
    public function __construct(
        #[StrongPassword(minLength: 14)]
        public string $password,
    ) {
    }
}
```

Ein gültiger Wert enthält mindestens die konfigurierte Länge sowie Kleinbuchstaben, Großbuchstaben, Zahl und Sonderzeichen. `null` und leerer String werden nicht als Pflichtfeld behandelt; dafür wird zusätzlich `#[Assert\NotBlank]` verwendet.

Nicht-String-Werte erzeugen eine Violation. Die Message kann wie bei normalen Symfony-Constraints angepasst und gruppiert werden.

## Grenzen

- Komplexität ist kein Ersatz für kompromittierte Passwortlisten oder Rate Limiting.
- Hashing übernimmt Symfony PasswordHasher, nicht der Validator.
- Fachliche Passwortregeln können eigene Constraints ergänzen.

Weiter: [User](user.md) und [Security](security.md).
