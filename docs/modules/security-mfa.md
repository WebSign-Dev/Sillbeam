<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Security MFA

`Nexum\Security\Mfa` ergänzt TOTP, Recovery Codes und kurzlebige Second-Factor-Challenges. MFA ist eine Fähigkeit; ihre HTTP-Routen werden separat exponiert.

## Aktivierung

```yaml
nexum:
    doctrine: { enabled: true }
    security:
        enabled: true
        mfa:
            enabled: true
            issuer: Nexum
            challenge_ttl: 300
            max_attempts: 5
            api:
                enabled: true
                projects: [api]
```

## Enrollment

1. Authentifizierter User startet TOTP-Enrollment.
2. Nexum erzeugt ein Secret und eine Provisioning URI.
3. Das Secret wird verschlüsselt als `TotpEnrollment` gespeichert.
4. Erst ein gültiger TOTP-Code bestätigt Enrollment.
5. Danach werden einmalig Recovery Codes zurückgegeben; gespeichert werden nur Hashes.

## Login-Challenge

Bei aktivem MFA stellt ein korrekter Passwortlogin nicht sofort ein Access Token aus. `MfaSecondFactorGate` erzeugt eine kurzlebige `MfaChallenge`. Erst TOTP oder ein unbenutzter Recovery Code schließt die Challenge ab.

Ungültige, abgelaufene, verbrauchte oder zu häufig getestete Challenges liefern generische Fehler, um keine sensiblen Zustände offenzulegen.

## Schutzmechanismen

- Secret-Verschlüsselung über `SecretCipher`
- gehashte Recovery Codes
- Ablaufzeit und maximale Versuche
- Challenge wird nach Erfolg verbraucht
- Rate Limiting nach IP und Subject
- kein Secret in Logs oder normalisierten User-Daten

## Öffentliche Routen

Bei aktivierter API stehen Status, Enrollment, Bestätigung, Verifikation und Deaktivierung unter dem MFA-Routenpräfix zur Verfügung. Die exakte Sichtbarkeit wird im Project Mode durch `api.projects` begrenzt.

## Grenzen

- Kein SMS- oder E-Mail-OTP-Provider.
- Keine automatische Project-Isolation der MFA-Daten.
- Kein automatisches Public Exposure.
- Schlüsselverwaltung und Rotation des SecretCipher bleiben Betriebsverantwortung.

Weiter: [MFA-Guide](../guides/mfa.md), [Security](security.md) und [Security-Checkliste](../operations/security-checklist.md).
