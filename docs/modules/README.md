<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum" width="520"></p>

# Komponentenübersicht

Alle Komponenten werden gemeinsam als `benjaminoeffinger/nexum` installiert. Die Bezeichnung „Modul“ in diesem Verzeichnis beschreibt interne Framework-Komponenten; Anwendungs-Modules mit `#[NexumModule]` sind in [Module anlegen](../guides/modules.md) dokumentiert.

## Fundament und Architektur

| Namespace | Aufgabe | Dokumentation |
| --- | --- | --- |
| `Nexum\Core` | markerfreie Basiskontrakte und Subject-ID | [Core](core.md) |
| `Nexum\Architecture` | Discovery- und Graph-Compiler | [Architecture](architecture.md) |
| `Nexum\Module` | Descriptor, Definition und Registry | [Module-System](module-system.md) |
| `Nexum\Project` | Project-Definition, Registry und Context | [Project-System](project-system.md) |
| `Nexum\Routing` | architekturabhängiger Symfony Route Loader | [Routing](routing.md) |
| `Nexum\DependencyInjection` | Einzelbundle-Konfiguration und Service Loading | [Dependency Injection](dependency-injection.md) |
| `Nexum\Kernel` | optionaler Komfort-Kernel und Laufzeitinfo | [Kernel](kernel.md) |
| `Nexum\Entity` | UUID-, Timestamp- und Soft-Delete-Basisklassen | [Entity](entity.md) |
| `Nexum\Doctrine` | Store, Listener und Soft-Delete-Filter | [Doctrine](doctrine.md) |
| `Nexum\Exception` | gemeinsame Exception-Typen | [Exception](exception.md) |
| `Nexum\Http` | Request-ID und Client-Kontext | [HTTP](http.md) |
| `Nexum\Serializer` | sicherer Symfony-Serializer-Adapter | [Serializer](serializer.md) |
| `Nexum\Validator` | wiederverwendbare Constraints | [Validator](validator.md) |

Diese Bereiche bilden gemeinsam die Discovery- und Compile-Pipeline. Die Anwendungsseite ist in [Architektur](../architecture/overview.md), [Modules](../guides/modules.md) und [Projects](../guides/projects.md) beschrieben.

## API, Benutzer und Sicherheit

| Namespace | Aufgabe | Dokumentation |
| --- | --- | --- |
| `Nexum\Api` | deklarative REST-Ressourcen | [API](api.md) |
| `Nexum\Security` | Token, Permissions und SecurityContext | [Security](security.md) |
| `Nexum\Security\Mfa` | TOTP, Recovery Codes und Challenges | [MFA](security-mfa.md) |
| `Nexum\Security\Passkey` | WebAuthn-Registrierung und Login | [Passkeys](security-passkey.md) |
| `Nexum\User` | Doctrine-Userbasis und Provider | [User](user.md) |
| `Nexum\Management` | read-only Architekturdiagnose | [Management](management.md) |

Security-Fähigkeiten und öffentliche APIs werden getrennt aktiviert. Siehe [Konfigurationsreferenz](../reference/configuration.md#api-exposition-im-project-mode).

## Infrastruktur

| Namespace | Aufgabe | Dokumentation |
| --- | --- | --- |
| `Nexum\Filesystem` | sicherer lokaler/Flysystem-Storage | [Filesystem](filesystem.md) |
| `Nexum\Mailer` | Provider-neutraler Mailversand | [Mailer](mailer.md) |
| `Nexum\Messenger` | schlanke MessageBus-Abstraktion | [Messenger](messenger.md) |
| `Nexum\QrCode` | SVG-QR-Code-Generierung | [QR Code](qrcode.md) |
| `Nexum\Logger` | Kontextsanitization und Redaction | [Logger](logger.md) |
| `Nexum\Health` | aggregierbare Health Checks | [Health](health.md) |
| `Nexum\Client` | Client-Identifikation | [Client](client.md) |

## Provider-neutrale Fachbausteine

| Namespace | Aufgabe | Dokumentation |
| --- | --- | --- |
| `Nexum\Payment` | Money und Payment-Provider-Verträge | [Payment](payment.md) |
| `Nexum\Search` | Search Query/Result und Provider | [Search](search.md) |
| `Nexum\Notification` | Notifications und Channels | [Notification](notification.md) |
| `Nexum\Reporting` | Reports sowie CSV-/JSON-Renderer | [Reporting](reporting.md) |
| `Nexum\Location` | Coordinates und PostalAddress | [Location](location.md) |

Diese Klassen sind bewusst unabhängig vom ProjectContext. Value Objects und Models können direkt mit `new` verwendet werden. Externe SDKs wie Stripe, S3 oder OpenSearch gehören in eigene Adapter der Anwendung.
