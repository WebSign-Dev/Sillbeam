<p align="center">
  <img src="assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520">
</p>

# Nexum-Dokumentation

> Ein Package. Ein Bundle. Klare Module. Optionale Projects.

Nexum ist ein modularer, API-orientierter Backend-Baukasten für PHP 8.4 und Symfony 7.4. Er reduziert wiederkehrende Infrastruktur, ohne Symfony zu ersetzen oder wichtige fachliche Entscheidungen zu verstecken.

## Schnellnavigation

| Bereich | Inhalt |
| --- | --- |
| [Erste Schritte](guides/getting-started.md) | Installation, Bundle, Konfiguration und erstes Modul |
| [Architektur](architecture/overview.md) | Zielbild, Build-Pipeline, Grenzen und Laufzeitmodell |
| [Module](guides/modules.md) | Discovery, Abhängigkeiten, Services, Entities und Controller |
| [Projects](guides/projects.md) | Hostbereiche, Shared/Private Modules und ProjectContext |
| [Konfigurationsreferenz](reference/configuration.md) | Vollständiger `nexum:`-Konfigurationsbaum |
| [Öffentliche API](reference/public-api.md) | Attribute, Interfaces, Basisklassen und Value Objects |
| [Entities & Doctrine](guides/entities.md) | Basisklassen, Zeitstempel, Soft Delete und EntityStore |
| [REST API](guides/api.md) | Ressourcen, CRUD, Filter, Pagination, Scope und Permissions |
| [Security](guides/security.md) | Bearer-Token, Permissions und sichere Aktivierung |
| [MFA](guides/mfa.md) / [Passkeys](guides/passkeys.md) | Zweiter Faktor und WebAuthn |
| [Infrastruktur](guides/infrastructure.md) | Filesystem, Mailer, Messenger und QR-Codes |
| [Komponentenübersicht](modules/README.md) | Alle Nexum-Namespace-Module und ihre öffentliche API |
| [Test-Apps](development/test-apps.md) | Reale Application-, Project- und Mischszenarien |
| [Qualität & Tests](development/testing.md) | PHPUnit, PHPStan, Deptrac und Coding Style |
| [CLI-Referenz](reference/cli.md) | Read-only Architektur- und Diagnosebefehle |
| [Fehlerdiagnose](operations/troubleshooting.md) | Discovery-, Routing-, Host- und Integrationsprobleme lösen |
| [Security-Checkliste](operations/security-checklist.md) | Produktionsfreigabe strukturiert prüfen |
| [Migration](migration/modular-monolith.md) | Breaking Cut von Packages/Teil-Bundles zum Einzelpackage |

## Das Nexum-Modell

```text
Symfony-Anwendung
├── normaler Symfony-Code
│   ├── Controller/
│   ├── Entity/
│   └── Service/
├── Shared Modules
│   └── Module/*
└── optionale Projects
    └── Project/*
        ├── Controller/
        └── Module/*       # project-privat
```

Nexum lässt sich stufenweise verwenden:

1. Reine Klassen wie `Money`, `Coordinates` oder Reports funktionieren ohne Kernel.
2. Framework-Services werden über normales Symfony Dependency Injection verwendet.
3. Modules strukturieren fachliche Bereiche und Abhängigkeiten.
4. Projects strukturieren Hosts und öffentlich sichtbare Oberflächen.

An keinem Punkt übernimmt Nexum die Anwendung vollständig. Symfony-Routing, Container, EventDispatcher, Messenger, Security und Doctrine bleiben die technischen Grundlagen.

## Stabilitäts- und Sicherheitsversprechen

- Descriptoren sind Metadatenklassen, keine Services.
- Class-Strings sind technische Identitäten; IDs sind für Konfiguration, Routen und Diagnose bestimmt.
- Project-Aktivierung isoliert Routen, aber nicht automatisch Services, Subscriber, Handler, Daten oder Tokens.
- Transaktionen und physische Löschungen bleiben explizit.
- Auth-, MFA-, Passkey- und Management-Routen erscheinen niemals allein durch die Installation.
- Unbekannte Hosts werden im Multi-Project Mode abgewiesen.

## Orientierung im Repository

```text
nexum/
├── src/                 # Framework-Code unter Nexum\
├── apps/                # ausführbare Referenz- und Testanwendungen
├── tests/               # Unit- und Integrationstests
├── docs/                # dieses Handbuch
├── flex-recipe/         # vorbereitete externe Symfony-Flex-Recipe
├── composer.json
└── deptrac.yaml
```

Für den schnellsten Einstieg: [Installation und erstes Modul](guides/getting-started.md).
