<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Security Passkey

`Nexum\Security\Passkey` integriert WebAuthn für Registrierung und usernameless Login. Relying Parties werden im Project Mode bewusst pro Project aufgelöst.

## Application Mode

```yaml
nexum:
    security:
        passkeys:
            enabled: true
            relying_party:
                rp_id: app.example.com
                allowed_origins: ['https://app.example.com']
            api: { enabled: true }
```

## Project Mode

```yaml
nexum:
    security:
        passkeys:
            enabled: true
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
```

Jedes exponierte Project benötigt eine passende Relying Party. `RelyingPartyProvider` löst sie über den zustandslosen ProjectContext auf.

## Registrierung

1. Authentifizierter User fordert Options an.
2. `PasskeyManager` erzeugt eine Challenge.
3. Browser ruft `navigator.credentials.create()` auf.
4. Server validiert RP, Origin, Challenge und User Verification.
5. Credential-ID, Public Key, Counter und Subject-Bezug werden gespeichert.

## Authentifizierung

1. Anonymer Client fordert usernameless Options an.
2. Browser ruft `navigator.credentials.get()` auf.
3. `WebauthnServer` validiert Assertion und Origin gegen die aktuelle RP.
4. Counter und Credential-Zustand werden geprüft.
5. Nach Erfolg wird ein Nexum Access Token ausgegeben.

## Schutzmechanismen

- exakte Allowed Origins
- Project-spezifische RP-ID
- erforderliche User Verification
- kurzlebige, einmal verwendbare Challenges
- Signature-Counter-Prüfung
- Rate Limiting nach IP und Subject
- generische Fehler bei ungültigen Assertions

## Grenzen

- RP-ID und Origins werden niemals aus einem beliebigen Request übernommen.
- Alias-Hosts müssen mit WebAuthn-RP-Regeln bewusst abgestimmt sein.
- Projects sind keine Credential-Datenisolation.
- Account Recovery bleibt eine fachliche Anwendungspflicht.

Weiter: [Passkey-Guide](../guides/passkeys.md), [Projects](../guides/projects.md) und [Security-Checkliste](../operations/security-checklist.md).
