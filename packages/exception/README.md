# nexum/exception

Kleine gemeinsame Exception-Hierarchie für stabile Fehlersemantik.

## Installation

    composer require nexum/exception

## Öffentliche API

NexumException, DomainException, InfrastructureException, NotFoundException. Symfony-Dienste bleiben dekorier- und konfigurierbar; interne Implementierungen sind kein Stabilitätsvertrag.

## Entwicklung

Tests, PHPStan, Architekturregeln und Coding Style werden zentral im Nexum-Monorepo ausgeführt.
