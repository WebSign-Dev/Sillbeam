<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Öffentliche API nach Bereich

Diese Seite ist eine Orientierung für Anwendungsentwickler. Interfaces, Attribute, Value Objects und ausdrücklich dokumentierte Basisklassen sind bevorzugte Integrationspunkte. Interne Compiler-, Discovery- und konkrete Infrastrukturklassen können sich vor 1.0 ändern.

## Architektur

| API | Zweck |
| --- | --- |
| `Nexum\Module\Attribute\NexumModule` | Module-Descriptor markieren |
| `Nexum\Project\Attribute\NexumProject` | Project-Descriptor und direkte Shared Modules definieren |
| `Nexum\Module\ModuleRegistryInterface` | Module nach Class-String und Project lesen |
| `Nexum\Project\ProjectRegistryInterface` | Projects nach ID, Class-String oder Host lesen |
| `Nexum\Project\ProjectContextInterface` | aktuelles Request-Project und aktive Module abfragen |
| `Nexum\NexumBundle` | einziger Symfony-Bundle-Einstieg |
| `Nexum\Kernel\NexumKernel` | optionaler Komfort-Kernel |

## Entity und Persistenz

| API | Zweck |
| --- | --- |
| `Nexum\Entity\AbstractEntity` | UUIDv7-ID |
| `Nexum\Entity\AbstractIntegerEntity` | expliziter Legacy-Integer-Fall |
| `Nexum\Entity\AbstractTimestampedEntity` | `createdAt` und `updatedAt` |
| `Nexum\Entity\AbstractSoftDeletableEntity` | zusätzlicher `deletedAt`-Zustand |
| `Nexum\Doctrine\Store\EntityStoreInterface` | persist/delete/restore/purge/flush/transactional |

Persistieren und Flush bleiben getrennt. `purge()` ist die bewusst physische Löschung.

## API

| API | Zweck |
| --- | --- |
| `Nexum\Api\Attribute\ApiResource` | Operationen, Felder, Filter, Sortierung, Permissions und Scope deklarieren |
| `Nexum\Api\Controller\AbstractResourceController` | Standard-CRUD implementieren |
| `Nexum\Api\Controller\AbstractApiController` | Response- und Problem-Helfer für eigene Endpunkte |
| `Nexum\Api\Scope\ResourceScope` | `DENY`, `SUBJECT` oder `GLOBAL` wählen |
| `Nexum\Api\Scope\ResourceScopeProviderInterface` | anwendungsspezifischen Datenscope bereitstellen |
| `Nexum\Api\Serializer\ApiResponseFactoryInterface` | konsistente API-Hüllen erzeugen |

## Security und User

| API | Zweck |
| --- | --- |
| `Nexum\Security\Authorization\SecurityContextInterface` | Benutzer und Permissions lesen |
| `Nexum\Security\Contract\SubjectUserInterface` | stabile Subject-Identität |
| `Nexum\Security\Contract\PermissionAwareUserInterface` | direkte Permissions bereitstellen |
| `Nexum\Security\Authentication\AccessTokenManager` | opaque Access Tokens ausstellen/widerrufen |
| `Nexum\User\Entity\AbstractUser` | erweiterbare Doctrine-Userbasis |
| `Nexum\User\Security\PasswordManagerInterface` | Passwörter hashen und prüfen |

## Infrastruktur

| Contract | Standardimplementierung |
| --- | --- |
| `StorageInterface` | `LocalStorage` beziehungsweise `FlysystemStorage` |
| `MailerInterface` | `SymfonyMailer` |
| `MessageBusInterface` | `SymfonyMessageBus` |
| `QrCodeGeneratorInterface` | `EndroidQrCodeGenerator` |
| `KernelInformationInterface` | `KernelInformation` |

Alle Implementierungen können über normale Symfony-Aliase ersetzt oder dekoriert werden.

## Reine PHP-Bausteine

- `Nexum\Payment\Money`, `PaymentRequest`, `PaymentResult`
- `Nexum\Location\Coordinates`, `PostalAddress`
- `Nexum\Reporting\Report`, `CsvReportRenderer`, `JsonReportRenderer`
- `Nexum\Search\SearchQuery`, `SearchHit`, `SearchResult`
- `Nexum\Notification\Notification`
- `Nexum\Health\HealthResult`, `HealthStatus`

Diese Klassen benötigen keinen ProjectContext und grundsätzlich keinen Symfony-Kernel. Bereichsspezifische Details stehen in der [Komponentenübersicht](../modules/README.md).
