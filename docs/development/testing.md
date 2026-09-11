<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Qualität und Tests

## Standardprüfungen

```bash
composer test
composer analyse
composer architecture
composer cs:check
composer validate --strict
```

| Prüfung | Zweck |
| --- | --- |
| PHPUnit | Unit-, Container-, Routing-, HTTP-, Doctrine- und Security-Verhalten |
| PHPStan Level 8 | Typ- und Kontrollflussfehler in Framework, Tests und Beispiel-Apps |
| Deptrac | Abhängigkeitsgrenzen zwischen Core, Entity, Module, Project, Routing und Integrationen |
| PHP-CS-Fixer | einheitlicher Symfony-orientierter Stil |
| Composer Validate | Package- und App-Metadaten |

## Testschichten

### Unit Tests

Unit Tests prüfen ID-Ableitung, Discovery-Optionen, Graphen, Konfigurationsregeln, Value Objects und Infrastruktur ohne vollständigen HTTP-Lifecycle.

### Kernel-Integration

Mehrere Stock-Symfony-Kernel registrieren ausschließlich `NexumBundle`. Getestet werden Containeraufbau, Service Discovery, Doctrine-Mappings, optionale Integrationen und die Gleichwertigkeit des optionalen `NexumKernel`.

### HTTP und Routing

Die Tests senden echte Requests gegen die App-Kernel. Erwartete 404- und 400-Fälle gehören zu den Sicherheitsnachweisen: deaktivierte Routen, falsche Project-Hosts und untrusted Hosts bleiben unerreichbar.

### Security

Token-Lifecycle, Permission-Prüfung, MFA, Recovery Codes und Passkey-Challenges laufen gegen echte Symfony-Security- und Doctrine-Konfiguration. Rate-Limiter-Caches werden pro Testfall isoliert, damit wiederholte Suite-Läufe reproduzierbar bleiben.

### Purity

Reine Nexum-Klassen und Entity-Basisklassen werden ohne gestarteten Kernel instanziiert. Dieser Test verhindert, dass Core- oder Value-Bereiche unbemerkt vom Architecture Layer abhängig werden.

## Neue Szenarien hinzufügen

1. Für ein vollständiges Nutzungsszenario eine kleine App unter `apps/<name>` anlegen.
2. Genau `FrameworkBundle` und `NexumBundle` registrieren, sofern keine Integration weitere Bundles benötigt.
3. Ein eigenes PSR-4-Mapping in der App-`composer.json` und im Root-`autoload-dev` ergänzen.
4. Einen isolierten Kernel-Cachepfad verwenden.
5. Nicht nur Definitionen, sondern mindestens einen echten HTTP- oder Service-Lifecycle prüfen.
6. PHPStan und Coding Style auf den neuen App-Quellpfad erweitern.

Die aktuelle Matrix ist in [test-apps.md](test-apps.md) beschrieben.
