<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Passkeys verwenden

Das Modul `Nexum\\Security\\Passkey` stellt eine reine JSON-API für WebAuthn Level 3 bereit. Browser oder Apps rufen die Options-Routen auf, übergeben die Daten an `navigator.credentials` und senden das Ergebnis an die jeweilige Abschlussroute zurück.

## Konfiguration

```yaml
nexum:
    security:
        passkeys:
            enabled: true
            relying_party:
                rp_id: example.test
                allowed_origins: ['https://example.test']
            challenge_ttl: 300
            max_attempts: 5
            api:
                enabled: true
```

`rp_id` muss zur produktiven Domain passen. Origins werden exakt zugelassen und müssen HTTPS verwenden. Wird das Modul in der Harbor-Test-App aktiviert, sind dafür `nexum-test-app.localhost` als RP-ID und `https://nexum-test-app.localhost` als Origin zu verwenden.

In Project Mode wird stattdessen unter `relying_parties.<project-id>` je Project eine RP-ID mit Origins konfiguriert. Die zentrale `type: nexum`-Route importiert ausschließlich bewusst freigeschaltete Passkey-Endpunkte.

## Registrierung

1. `POST /api/auth/passkeys/register/options` mit Bearer Token aufrufen.
2. `data.publicKey` an `navigator.credentials.create({publicKey})` übergeben.
3. Challenge-ID, serialisiertes Credential und optionales Label an `POST /api/auth/passkeys/register` senden.

```json
{
  "challenge": "0199...",
  "label": "Notebook",
  "credential": {
    "id": "base64url...",
    "rawId": "base64url...",
    "type": "public-key",
    "response": {
      "clientDataJSON": "base64url...",
      "attestationObject": "base64url..."
    }
  }
}
```

Nexum fordert User Verification und einen discoverable Credential an. Attestation steht bewusst auf `none`, sodass keine unnötigen Geräteidentitäten gesammelt werden. Registrierte Passkeys liefert `GET /api/auth/passkeys`; `DELETE /api/auth/passkeys/{id}` entfernt einen Schlüssel.

## Passwortloser Login

1. `POST /api/auth/passkeys/authenticate/options` ohne Anmeldung aufrufen.
2. `data.publicKey` an `navigator.credentials.get({publicKey})` übergeben.
3. Challenge-ID und Assertion an `POST /api/auth/passkeys/authenticate` senden.

```json
{
  "challenge": "0199...",
  "credential": {
    "id": "base64url...",
    "rawId": "base64url...",
    "type": "public-key",
    "response": {
      "clientDataJSON": "base64url...",
      "authenticatorData": "base64url...",
      "signature": "base64url...",
      "userHandle": "base64url..."
    }
  }
}
```

Binärfelder müssen vor dem JSON-Versand Base64URL-kodiert werden. Moderne Browser liefern mit `PublicKeyCredential#toJSON()` bereits ein passendes Objekt; andernfalls übernimmt diese kleine Umwandlung der aufrufende Client.

Der Ablauf ist usernameless und verrät deshalb vorab keinen Benutzerbestand. Nach erfolgreicher kryptografischer Prüfung gibt die API ein reguläres Nexum Access Token zurück.

Challenges sind kurzlebig, nur einmal verwendbar und fehlversuchsbegrenzt. Prüfung, Challenge-Verbrauch, Signatur-Counter-Update und Token-Ausgabe bilden eine gesperrte Datenbanktransaktion; paralleles Replay kann deshalb nicht zweimal erfolgreich sein. Die Routen besitzen zusätzlich getrennte Sliding-Window-Limits nach vertrauenswürdiger Client-IP und – sobald das Credential bekannt ist – stabiler Benutzer-UUID. Credential IDs werden nur gehasht indiziert; der öffentliche Schlüssel, Signatur-Counter und Backup-Status liegen im WebAuthn Credential Record. Ein ungültiger Counter wird abgelehnt, um geklonte Authenticatoren zu erkennen.

Parsing, Origin-/Challenge-Prüfung, COSE-Signaturprüfung, User Verification und Counter-Prüfung übernimmt [WebAuthn Framework 5.3](https://webauthn-doc.spomky-labs.com/v5.3). Nexum implementiert diese Kryptografie nicht selbst.
