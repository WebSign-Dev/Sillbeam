<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Sicherheits- und Produktionscheckliste

## Exponierte Oberfläche

- [ ] `security.api.enabled`, `security.mfa.api.enabled`, `security.passkeys.api.enabled` und `management.api.enabled` sind bewusst gesetzt.
- [ ] Im Project Mode besitzt jede aktive API eine enge `projects`-Allowlist; `['*']` ist dokumentiert begründet.
- [ ] `debug:router` enthält keine unerwarteten Auth-, Management- oder Modulrouten.
- [ ] Admin- und API-Hosts sind zusätzlich über Symfony `trusted_hosts` begrenzt.
- [ ] Unbekannte Hosts liefern keinen Defaultinhalt.

## Authentifizierung

- [ ] `APP_SECRET` und weitere Schlüssel stammen aus einem Secret Store, nicht aus Git.
- [ ] Access-Token-TTL passt zum Risiko der Anwendung.
- [ ] TLS ist für Login-, MFA- und Passkey-Endpunkte erzwungen.
- [ ] Login-, MFA- und Passkey-Rate-Limiter verwenden einen persistenten produktionsgeeigneten Cache.
- [ ] Token werden ausschließlich gehasht gespeichert und Widerruf ist getestet.

## MFA und Passkeys

- [ ] TOTP-Secrets werden verschlüsselt gespeichert.
- [ ] Recovery Codes werden nur einmal angezeigt und gehasht gespeichert.
- [ ] Jede freigeschaltete Passkey-Project-ID besitzt eine Relying Party.
- [ ] `rp_id` entspricht der tatsächlichen registrierbaren Domain.
- [ ] `allowed_origins` enthält ausschließlich exakte HTTPS-Origins.
- [ ] Canonical Hosts und Aliase wurden mit realen Browsern getestet.

## Daten und Autorisierung

- [ ] Jede `ApiResource` besitzt einen bewussten Scope (`SUBJECT` oder `GLOBAL`); der Standard `DENY` wurde nicht versehentlich belassen.
- [ ] `writableFields`, Filter und Sortierfelder sind Allowlisten.
- [ ] Fremde Subject-IDs liefern 404 und leaken keine Existenz.
- [ ] Projects werden nicht mit Tenant- oder Datenisolation verwechselt.
- [ ] Physische Löschungen verwenden ausschließlich bewusstes `purge()`.
- [ ] Transaktionsgrenzen und `flush()` sind im Anwendungscode sichtbar.

## Betrieb

- [ ] Composer-Lockfile und Produktionsabhängigkeiten sind reproduzierbar.
- [ ] `composer test`, `composer analyse`, `composer architecture` und `composer cs:check` laufen in CI.
- [ ] Symfony-Container und Router werden beim Deployment frisch gewärmt.
- [ ] Logs redigieren Tokens, Passwörter, Cookies und sensible Payloads.
- [ ] Dateispeicher besitzt passende Pfad-, Größen- und Berechtigungsgrenzen.

Siehe [Konfigurationsreferenz](../reference/configuration.md), [Security](../guides/security.md), [MFA](../guides/mfa.md) und [Passkeys](../guides/passkeys.md).
