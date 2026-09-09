# MFA verwenden

`nexum/security-mfa` ergänzt den Passwort-Login um TOTP nach RFC 6238 und einmalig verwendbare Recovery-Codes. Ist für einen Benutzer kein bestätigtes Enrollment vorhanden, verhält sich der normale Login unverändert.

## Konfiguration

```yaml
nexum_security_mfa:
    issuer: Nexum
    challenge_ttl: 300
    max_attempts: 5
```

Die Routen werden einmal importiert:

```yaml
nexum_security_mfa:
    resource: '@NexumSecurityMfaBundle/config/routes.php'
    type: php
    prefix: /api/auth/mfa
```

## Einrichtung

Ein bereits angemeldeter Benutzer startet mit `POST /api/auth/mfa/totp`. Die Antwort enthält das Secret und eine `otpauth://`-Provisioning-URI. Das Secret wird nur in dieser Antwort im Klartext ausgegeben; in Doctrine liegt es authentifiziert verschlüsselt mit Sodium Secretbox.

Der Benutzer bestätigt die Einrichtung über `POST /api/auth/mfa/totp/confirm`:

```json
{"code":"123456"}
```

Die zehn zurückgegebenen Recovery-Codes müssen einmalig sicher angezeigt und vom Benutzer gespeichert werden. Serverseitig liegen nur keyed Hashes. Nach erfolgreicher Aktivierung werden alle älteren Access Tokens widerrufen.

## Login

Bei aktiviertem MFA antwortet `POST /api/auth/token` mit HTTP 202 statt mit einem Token:

```json
{
  "data": {
    "mfa_required": true,
    "challenge": "0199...",
    "methods": ["totp", "recovery_code"],
    "expires_at": "2026-09-07T18:00:00+00:00"
  }
}
```

`POST /api/auth/mfa/verify` erhält Challenge und TOTP beziehungsweise Recovery-Code:

```json
{
  "challenge": "0199...",
  "code": "123456"
}
```

Erst danach wird das Access Token ausgegeben. Challenges laufen standardmäßig nach fünf Minuten ab, erlauben höchstens fünf Fehlversuche und sind nur einmal verwendbar. Ein TOTP-Zeitschritt sowie jeder Recovery-Code können ebenfalls nur einmal verwendet werden.

`GET /api/auth/mfa/status` liefert den Status. `DELETE /api/auth/mfa/totp` deaktiviert MFA mit dem Body `{"code":"123456"}` nach erneuter TOTP- oder Recovery-Code-Prüfung und widerruft wiederum bestehende Tokens.

Enrollment, Bestätigung, Challenge-Verbrauch, Recovery-Code-Verbrauch und Deaktivierung laufen in Datenbanktransaktionen mit Schreibsperren. Zwei parallele Requests können dadurch weder dieselbe Challenge noch denselben TOTP-Zeitschritt oder Recovery-Code erfolgreich verwenden.

Die sicherheitsrelevanten Routen besitzen zusätzlich ein automatisch konfiguriertes Symfony Sliding-Window-Rate-Limit. Es wird unabhängig nach vertrauenswürdig ermittelter Client-IP und stabiler Benutzer-UUID geführt; wechselnde IP-Adressen umgehen das Account-Limit daher nicht.

Die TOTP-Berechnung übernimmt [OTPHP](https://github.com/Spomky-Labs/otphp). Nexum erzeugt keine eigene OTP-Kryptografie.
