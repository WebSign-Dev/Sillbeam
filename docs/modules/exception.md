# Modul: exception

Kleine gemeinsame Exception-Hierarchie für stabile Fehlersemantik.

## Bereitstellung

Das Modul ist Bestandteil des Composer-Pakets `benjaminoeffinger/nexum` und wird nicht separat installiert.

## Öffentliche API

NexumException, DomainException, InfrastructureException, NotFoundException. Symfony-Dienste bleiben dekorier- und konfigurierbar; interne Implementierungen sind kein Stabilitätsvertrag.

## Entwicklung

Tests, PHPStan, Architekturregeln und Coding Style werden zentral im Nexum-Package ausgeführt.
