# Modul: security

Authentifizierung, Token und fein aufgelöste Permissions.

## Bereitstellung

Das Modul ist Bestandteil des Composer-Pakets `benjaminoeffinger/nexum` und wird nicht separat installiert.

## Öffentliche API

SecurityContextInterface, AccessTokenManager, PermissionResolver. Symfony-Dienste bleiben dekorier- und konfigurierbar; interne Implementierungen sind kein Stabilitätsvertrag.

## Entwicklung

Tests, PHPStan, Architekturregeln und Coding Style werden zentral im Nexum-Package ausgeführt.
