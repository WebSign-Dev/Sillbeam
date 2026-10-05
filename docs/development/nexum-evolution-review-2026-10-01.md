# Nexum: Codeanalyse zur nächsten Evolution

Stand: 1. Oktober 2026. Referenz: die bereitgestellte Zielbeschreibung einer optionalen, konfigurierbaren Modulschicht für Symfony. Analysierter Commit: `9103b57`; vorhandene lokale Änderungen sind nicht Bestandteil dieses Reviews.

## Einschätzung

Die neue Richtung lässt sich auf dem vorhandenen Kern aufbauen. `ArchitectureDiscovery`, `ArchitectureCompiler`, unveränderliche Definitionen, Registries und der normale Symfony-Bundle-Einstieg sind geeignete Grundlagen. Application Mode, Project Mode und der parallele Betrieb normalen Symfony-Codes sind bereits durch Integrationstests vertreten.

Der wichtigste Umbau betrifft die Build-Pipeline: Alle Integrationen müssen dieselbe konfigurierte und validierte Architektur verwenden. Aktuell geschieht das für Services und Routing weitgehend, für Doctrine jedoch nicht. Dazu kommen ein eigener Service-Loader mit abweichender Symfony-Semantik und mehrere Fehler bei der Bestimmung von Modulgrenzen.

Die neue Evolution sollte daher zuerst die bestehenden Architekturversprechen zuverlässig machen, anschließend den Foundation-Umfang entfernen und dann flexible Discovery und tatsächliche Code-Abhängigkeiten ergänzen. Ein vollständiger Neustart ist dafür aus meiner Sicht nicht erforderlich.

## Prüfungen und Grenzen

Ausgeführt mit PHP 8.5.4, Symfony FrameworkBundle 8.1.6, Doctrine ORM 3.7.0 und DoctrineBundle 3.3.2:

| Prüfung | Ergebnis |
| --- | --- |
| `php vendor/bin/phpunit --configuration=phpunit.xml.dist` | 48 Tests, 445 Assertions, erfolgreich |
| `php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress` | Keine Fehler, Level 8 |
| `php vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress --no-cache` | 0 Violations, 0 Errors; 576 Uncovered Dependencies |
| PHP-CS-Fixer gemäß Composer-Script | Keine Änderungen erforderlich, 243 Dateien geprüft |
| `composer validate --strict --no-check-publish` | Gültig |
| Separate Reproduktionen in temporären Verzeichnissen | Die nachfolgend als reproduziert bezeichneten Fehler bestätigt |

Die Mindestversionen PHP 8.4 und Symfony 7.4 wurden in dieser Analyse nicht separat ausgeführt. Der externe Composer-/Flex-Installationslauf wurde ebenfalls nicht ausgeführt. Die vorhandenen PHPUnit-Recipe-Tests sind Teil der erfolgreichen Suite.

Schwerpunkt ist der Architekturkern einschließlich DI, Routing, Doctrine-Anbindung, Composer-Abhängigkeiten, Dokumentation und relevanter Tests. Dies ist keine vollständige Sicherheitsprüfung der später auszulagernden Security-/API-Komponenten. Produktionscode wurde nicht geändert.

Die 576 von Deptrac nicht erfassten Abhängigkeiten sind nicht automatisch Fehler: Die konfigurierte Layer-Matrix erfasst insbesondere externe Bibliotheken nicht vollständig. Außerdem validiert diese Root-Konfiguration Nexums interne Komponenten, nicht die deklarierte Modularchitektur einer Verbraucheranwendung.

## Bestätigte Probleme im vorhandenen Code

P1 bedeutet: vor der nächsten Evolution beheben. P2 bedeutet: mit dem betreffenden Architekturumbau beheben.

### 1. P1: Deaktivierte und unbenutzte Module erhalten Doctrine-Mappings

Quelle: [NexumExtension.php](/home/oeffe/PhpstormProjects/nexum/src/DependencyInjection/NexumExtension.php:103).

`prependDoctrine()` iteriert über `$this->discovery->modules`. Aktivierung und Project-Zuordnung werden erst später in `load()` durch `ArchitectureCompiler` ausgewertet. Die Doctrine-Integration verwendet damit den gesamten Discovery-Katalog, während Service-Registrierung und Routing den aktiven Graphen verwenden.

Reproduktion: Ein Modul `off` mit `enabled: false` und einem `Entity`-Verzeichnis liefert weiterhin den Mapping-Prefix `AuditDoctrine\Module\Off\Entity`. Dasselbe Problem betrifft im Project Mode gefundene Shared Modules, die keinem aktiven Project-Graphen angehören.

Folge: Nexum kann diese Entities weiterhin in Doctrine-Metadaten und damit in Schema-/Migrationsberechnungen einbringen. `enabled: false` erfüllt die gewünschte Bedeutung nicht.

Empfehlung: Konfiguration normalisieren und die Architektur bereits vor dem Prepend der Doctrine-Konfiguration kompilieren. Diese Architektur anschließend auch für Service-Registrierung, Routing und Registries verwenden. Doctrine-Mappings ausschließlich aus aktiven Modulen und den vorgesehenen Project-Bereichen ableiten. Raw-Konfiguration und verarbeitete Konfiguration dürfen keine getrennten Aktivierungsentscheidungen treffen.

### 2. P1: Der Service-Loader ignoriert native Symfony-Regeln

Quelle: [ArchitectureServiceLoader.php](/home/oeffe/PhpstormProjects/nexum/src/DependencyInjection/ArchitectureServiceLoader.php:16).

Der Loader traversiert Dateien selbst, lädt sie mit `require_once` und registriert konkrete Klassen mit `autowire()->setAutoconfigured(true)`. Das bildet die Discovery-Semantik von Symfony nicht vollständig ab.

Reproduziert:

- Eine Klasse mit `#[Exclude]` wird trotzdem als normaler Service registriert.
- Eine Klasse mit `#[When('dev')]` wird auch für eine Produktionsregistrierung aufgenommen.
- Ein Consumer mit einem Interface als Constructor-Typ scheitert am Autowiring, obwohl genau eine Implementierung im selben Modul vorhanden ist. Es wird kein entsprechender automatischer Interface-Alias erstellt.

Ein Vergleichslauf mit Symfonys `YamlFileLoader` im Environment `prod` berücksichtigt beide Ausschlussattribute und kompiliert denselben Interface-Consumer erfolgreich. Auch `#[AsAlias]` wird im Symfony-Dateiloader behandelt und gehört in die Kompatibilitätsprüfung.

Empfehlung: Nexum bestimmt Modulgrenzen und Service-Ressourcen; die eigentliche Klassenregistrierung delegiert es an den nativen Symfony-Dateiloader. Damit bleiben Environment-Regeln, Aliasbildung, Ressourcenverfolgung und normale Symfony-Konfiguration konsistent. Eigene Ausschlüsse für Descriptoren und die empfohlenen Entity-/DTO-Bereiche können als Defaults mitgegeben werden.

Die Symfony-Dokumentation beschreibt die Ausschluss- und Environment-Regeln: [Service Container](https://symfony.com/doc/current/service_container.html#excluding-services).

### 3. P1: Private Module lassen sich nicht unabhängig deaktivieren

Quelle: [ArchitectureCompiler.php](/home/oeffe/PhpstormProjects/nexum/src/Architecture/ArchitectureCompiler.php:38).

Im Project Mode werden alle Module mit passendem `projectClass` bedingungslos expandiert. `expand()` verlangt anschließend, dass das Modul aktiviert ist.

Reproduktion: Ein Project ohne deklarierte Module und ein privates `checkout` mit `project:shop:checkout.enabled: false` erzeugen `Active graph requires disabled module "checkout".` Auch ohne Dependency eines anderen Moduls bricht der Build ab.

Empfehlung: Automatisch zugeordnete private Module nur dann als Aktivierungswurzeln aufnehmen, wenn sie aktiviert sind. Wird ein deaktiviertes Modul ausdrücklich benötigt, beispielsweise durch eine Dependency oder eine explizite Project-Modulliste, bleibt der Build-Fehler richtig.

### 4. P1: Ein verwaistes privates Modul wird zum Shared Module

Quelle: [ModuleDiscovery.php](/home/oeffe/PhpstormProjects/nexum/src/Module/ModuleDiscovery.php:45) und [owningProject()](/home/oeffe/PhpstormProjects/nexum/src/Module/ModuleDiscovery.php:65).

Die erlaubte Pfadstruktur akzeptiert `Project/Orphan/Module/Secret/SecretModule.php`. Gibt es keinen passenden Project-Descriptor, liefert `owningProject()` jedoch `null`. Das Modul gilt dadurch als Shared Module.

Reproduktion: Ohne `OrphanProject.php` wird `SecretModule` als `shared` klassifiziert und im Application Mode aktiviert.

Empfehlung: Ein Pfad, der einen privaten Scope ausdrückt, muss einen gültigen Eigentümer besitzen. Fehlt dieser, muss Discovery einen Architekturfehler melden. Für flexible Roots sollte die Scope-Zuordnung später explizit modelliert werden und dieselbe Prüfung behalten.

### 5. P2: Mehrere Descriptoren dürfen denselben Modulbereich besitzen

Quelle: [ModuleDiscovery.php](/home/oeffe/PhpstormProjects/nexum/src/Module/ModuleDiscovery.php:46), [validateIdentities()](/home/oeffe/PhpstormProjects/nexum/src/Architecture/ArchitectureCompiler.php:115).

Zwei unterschiedlich benannte `*Module.php`-Descriptoren im selben Verzeichnis werden als zwei Module akzeptiert, solange IDs und Klassen unterschiedlich sind. Identitätsvalidierung prüft keine doppelte Directory-/Namespace-Zuständigkeit.

Reproduktion: `Module/A/AuditModule.php` und `Module/A/OtherModule.php` ergeben zwei gültige Definitionen mit identischem Modulverzeichnis.

Folge: Derselbe Code gehört mehreren Modulen. Deaktivierung eines Descriptors verhindert dann nicht die Registrierung des gemeinsamen Bereichs über den anderen. Ein zukünftiger Analyzer könnte Referenzen keinem eindeutigen Modul zuordnen.

Empfehlung: Eindeutige Zuständigkeit für jeden Modulbereich validieren. Überlappende Roots entweder ablehnen oder eine ausdrücklich definierte Ownership-Regel verwenden; keine zufällige Auflösung anhand der Scan-Reihenfolge. Dieselbe Prüfung ist für Project-Bereiche sinnvoll.

### 6. P2: Relative Discovery-Pfade hängen vom aktuellen Arbeitsverzeichnis ab

Quelle: [DiscoveryOptionsResolver.php](/home/oeffe/PhpstormProjects/nexum/src/Architecture/DiscoveryOptionsResolver.php:39).

`resolvePath()` ersetzt `%kernel.project_dir%`, reicht andere relative Pfade aber unverändert an `realpath()` weiter. Dieses arbeitet relativ zum Prozess-Arbeitsverzeichnis.

Reproduktion: Mit `kernel.project_dir=/tmp/nexum-audit-...` und `root: src` wird `/home/oeffe/PhpstormProjects/nexum/src` ausgewählt.

Empfehlung: Relative Pfade immer an `kernel.project_dir` binden. Danach Pfade kanonisieren, Existenz prüfen und ungültige Roots oder Namespace-Prefixe mit einer konkreten Konfigurationsmeldung zurückweisen. Identische PSR-4-Mappings aus `autoload` und `autoload-dev` vor der Ambiguitätsprüfung deduplizieren.

### 7. P2: Die Descriptor-Suche greift auf normalen Symfony-Code über

Quelle: [SourceInspector.php](/home/oeffe/PhpstormProjects/nexum/src/Architecture/SourceInspector.php:10), [ModuleDiscovery.php](/home/oeffe/PhpstormProjects/nexum/src/Module/ModuleDiscovery.php:27).

Die Discovery sucht im gesamten Root nach Dateinamen mit `Module.php` oder `Project.php`. Noch vor der Attributprüfung wird für jeden Treffer eine konkrete Klasse erwartet und die Datei gegebenenfalls ausgeführt.

Reproduktion: Eine normale, korrekt namespacete Datei `Contract/Module.php` mit `interface Module {}` und ohne Nexum-Attribut bricht die Discovery mit `must declare autoloadable class` ab.

Empfehlung: Zwischen möglichen Dateikandidaten und echten attributierten Descriptoren unterscheiden. Gewöhnlicher Code außerhalb von Nexum-Bereichen darf nicht allein wegen seines Namens zum Architekturfehler werden. Eine statische Kandidatenprüfung vermeidet außerdem, beliebige Treffer unnötig per `require_once` auszuführen.

## Lücken gegenüber der neuen Zielbeschreibung

### Flexible Modulstruktur fehlt noch

`DiscoveryOptions` enthält genau einen Root und einen Namespace. `ModuleDiscovery` und `ProjectDiscovery` erzwingen weiterhin feste Pfade per Regex. Ein anderer Root verschiebt lediglich die erwartete `Module/`-/`Project/`-Struktur; er erlaubt noch keine beliebige Struktur wie `src/Domain/Billing` oder mehrere parallele Discovery-Roots.

Auch die technischen Integrationsbereiche sind festgelegt: Controller- und Entity-Verzeichnisse werden über ihre Namen gesucht, `Entity/` und `Dto/` werden für Services hart ausgeschlossen.

Empfehlung: Mehrere Discovery-Bereiche mit Root, Namespace, Scope und Excludes zulassen. Die bisherige Struktur bleibt der Default. Attributierte Descriptoren bestimmen Module; reine Ordnernamen erzeugen keine Module. Controller-/Entity-Regeln bekommen sinnvolle Defaults und gezielte Abweichungsmöglichkeiten. Native Symfony-Konfiguration bleibt für Sonderfälle verfügbar.

Mit konfigurierten Regeln muss die Ownership von Dateien und Klassen eindeutig sein. Ein konfiguriertes Exclude sollte tatsächlich schon beim Traversieren greifen. Aktuell werden ausgeschlossene Unterverzeichnisse bei `namedDirectories()` trotzdem durchlaufen und lediglich Treffer herausgefiltert.

### Tatsächliche Code-Abhängigkeiten werden noch nicht geprüft

`validateDependencies()` sieht ausschließlich `ModuleDefinition::dependencies`. `ArchitectureValidationPass` kontrolliert lediglich das Vorhandensein der Registries und Aliase. Weder dieser Pass noch die Discovery analysiert die Referenzen im fachlichen Code.

Deshalb kann der aktuelle deklarierte Graph korrekt sein, während der Code undeclared Dependencies, direkte Zugriffe auf nur transitiv erreichbare Module oder unerlaubte Shared→Private-Zugriffe enthält.

Empfehlung: Einen statischen Analyzer auf Basis eines PHP-AST mit Namensauflösung ergänzen. PHP-Parser bietet dafür einen `NameResolver`: [Dokumentation des Projekts](https://github.com/nikic/PHP-Parser/blob/master/doc/component/Name_resolution.markdown). Das ist eine Umsetzungsempfehlung, keine bereits vorhandene Nexum-Funktion.

Der Analyzer benötigt zunächst einen Index `Klasse/Datei → Modul`. Danach kann er unter anderem Typdeklarationen, Vererbung, implementierte Interfaces, Traits, `new`, statische Aufrufe, Klassenkonstanten, Attribute, `instanceof` und Catch-Typen untersuchen. Ein ungenutztes `use`-Importstatement allein sollte keine tatsächliche Abhängigkeit erzeugen. Descriptor-Metadaten müssen von fachlichen Verwendungen unterschieden werden.

Jede erkannte modulübergreifende Verwendung wird gegen die direkten deklarierten Dependencies und die Scope-Regeln geprüft. Bei `A → B → C` legitimiert die transitive Kette keinen direkten Codezugriff von A auf C.

Diagnosen sollten Quellmodul, Zielmodul, referenzierte Klasse, Datei und Zeile enthalten. Dynamische Klassennamen, beliebige Strings und indirekte Container-Zugriffe gehören nur dann zum Versprechen, wenn sie tatsächlich zuverlässig auflösbar sind. PHPDoc- und Konfigurationsreferenzen können später als ausdrücklich dokumentierte Erweiterungen folgen.

`nikic/php-parser` ist aktuell im Lockfile vorhanden, aber nicht als direkte Nexum-Runtime-Abhängigkeit. Wenn der Build darauf angewiesen wird, muss Nexum diese Abhängigkeit selbst deklarieren.

### Zero Config benötigt konsistente Defaults

`Configuration` aktiviert Doctrine, API und Security standardmäßig. Eine Anwendung ohne DoctrineBundle scheitert dadurch ohne zusätzliche Nexum-Konfiguration. Die Flex-Recipe deaktiviert diese Integrationen zwar, die Defaults des Bundles verhalten sich dennoch anders.

Zusätzlich aktiviert `prepend()` immer Symfony Serializer und Validation. `loadNexumServices()` lädt immer HTTP-, Serializer- und Validator-Services; darunter befindet sich ein Response-Subscriber für `X-Request-Id`.

Für die neue reine Modulschicht sollten diese Foundation-Eingriffe entfallen. Eine leere Nexum-Konfiguration muss mit FrameworkBundle und NexumBundle funktionieren. Doctrine-Unterstützung sollte vom vorhandenen DoctrineBundle abhängen und ausdrücklich abschaltbar bleiben. Eigene Entities, Filter, API- oder Security-Regeln sind dafür nicht erforderlich.

### Architekturdiagnostik ist noch optional und unvollständig

`nexum:architecture` liegt im optionalen Management-Bereich und wird nur bei `management.enabled` registriert. Der Command zeigt aktive Zuordnungen, aber nicht den vollständigen Katalog einschließlich deaktivierter und unbenutzter Module. Pfade, Aktivierungsgründe, Integrationsbereiche und erkannte Code-Abhängigkeiten fehlen.

Empfehlung: Den CLI-Command in den Architekturkern übernehmen. Neben der menschenlesbaren Ausgabe ein JSON-Format für CI und Werkzeuge anbieten. `enabled` und `active` unterscheiden: Ein aktivierbares Shared Module kann im Project Mode unbenutzt bleiben. Den Grund für Aktivierung oder Nichtaktivierung als Teil des Build-Ergebnisses festhalten.

## Foundation sauber abtrennen

Die Kernbereiche sind bereits klein: Von 165 PHP-Dateien in `src` liegen 29 in Architecture, Module, Project, Kernel, DependencyInjection und Routing. Bundle und ArchitectureCommand kommen dazu. Der Umfang der Evolution entsteht vor allem durch die Verdrahtung und Migration.

| Bereich | Empfehlung |
| --- | --- |
| Architecture, Module, Project, Routing, NexumBundle | Nexum-Kern behalten und korrigieren |
| NexumKernel, AboutCommand, KernelInformation | Als schlanke, optionale beziehungsweise diagnostische Komfortfunktionen behalten |
| Management/Console/ArchitectureCommand | In den Kern verschieben |
| Doctrine-Modulmapping | Als Integrationslogik behalten |
| Entity-Basen, EntityStore, TimestampListener, SoftDeleteFilter | Aus Nexum entfernen/auslagern |
| API, Security, User, MFA, Passkeys | Aus Nexum entfernen/auslagern |
| Filesystem, Mailer, QR, Messenger-Abstraktionen | Aus Nexum entfernen/auslagern |
| HTTP-/Client-Utilities, Serializer-/Validator-Abstraktionen, Logger, Health und allgemeine Fachverträge | Nach ihrem eigenen Nutzen außerhalb des Nexum-Kerns beurteilen |

Das betrifft nicht nur Verzeichnisse. `NexumExtension`, `Configuration`, `NexumRouteLoader`, Composer-Anforderungen, Flex-Recipe, Skeleton, Beispiel-Apps, Testfixtures, Deptrac und Dokumentation müssen dieselbe Trennung abbilden.

Aktuell sind QR-Code, Flysystem, OTP, WebAuthn, Sodium, Security, Mailer, Messenger und Doctrine inklusive MigrationsBundle Composer-Pflichtabhängigkeiten, auch wenn ihre Nexum-Funktionen abgeschaltet sind. Abschalten verringert daher nicht den Installationsumfang. Nach der Trennung müssen nur die tatsächlich benötigten Kernkomponenten Pflicht sein; Doctrine kann als optionale Integration getestet und vorgeschlagen werden. Das MigrationsBundle ist für das Ableiten von ORM-Mappings selbst nicht erforderlich.

Eine konkrete Gegenabhängigkeit muss ebenfalls aufgelöst werden: `Security/Passkey/Configuration/RelyingPartyProvider` verlangt `Nexum\Project\ProjectContextInterface`. Bloßes Verschieben nach Strata würde Strata weiter von Nexum abhängig machen. Die Passkey-Komponente benötigt stattdessen einen eigenen fachlichen Context-/Relying-Party-Vertrag. Eine optionale Integration kann diesen mit Nexum verbinden; keines der beiden Kernpakete muss das andere voraussetzen.

Nicht jeder vorhandene Foundation-Baustein muss automatisch zum neuen Strata-Produkt werden. Zuerst Nexums Grenze ziehen; den Strata-Umfang anschließend eigenständig bestimmen. Für Verbraucher braucht die Entfernung der bisherigen öffentlichen Klassen eine dokumentierte Migration und eine klare Versionierungsentscheidung.

## Gezielte Optimierungen

### Graph-Expansion

`expand()` baut rekursiv Listen auf, kopiert sie per Array-Spread und traversiert bereits besuchte Dependencies erneut. Dedupliziert wird erst nach der Expansion. Bei überlappenden Dependency-Pfaden kann die Arbeit mit der Anzahl der Pfade stark wachsen.

Ein einmaliger synthetischer Lauf, bei dem jedes Modul die nächsten zwei Module referenziert, ergab:

| Module | Kanten | Aktive Module | Compile-Zeit |
| --- | --- | --- | --- |
| 10 | 17 | 10 | ca. 0,17 ms |
| 16 | 29 | 16 | ca. 3,05 ms |
| 22 | 41 | 22 | ca. 85,17 ms |

Dies ist kein Produktionsbenchmark. Zusammen mit dem rekursiven Code zeigt es aber einen konkreten Optimierungsbedarf.

Empfehlung: Pro Application-/Project-Graph eine visited-Menge verwenden und Module erst nach ihren Dependencies in die Ergebnisliste aufnehmen. Damit kann die Aktivierungsberechnung pro Graph entlang der besuchten Knoten und Kanten arbeiten. Project-Scope- und Disabled-Prüfungen beibehalten. Eine gemeinsame topologische Ordnung kann zusätzlich wiederverwendet werden.

### Discovery und Ressourcen

Project- und Module-Suche laufen separat über den kompletten Root, danach werden Controller und Entities erneut rekursiv gesucht. Für mehrere Roots und einen AST-Analyzer lohnt ein gemeinsamer Datei-/Symbolindex pro Build. Ressourcen und Ergebnisse dürfen jedoch nicht zu einem zweiten, eigenen Cache-System neben Symfony führen.

Der RouteLoader besitzt noch fest codierte Ressourcen für `src/Module`, `src/Project` und `config/packages/nexum*`. Bei der Erweiterung sollte er die tatsächlichen Discovery-Ressourcen erhalten. Symfony verfolgt zusätzlich die generierte Containerdatei; deshalb ist aus den festen Pfaden allein noch kein nachgewiesener Fehler des gesamten Router-Caches abzuleiten. Neue/gelöschte Module und Konfigurationsänderungen sollten ausdrücklich über Custom Roots getestet werden.

### Zuständigkeiten und Fehlermeldungen

Nach dem Entfernen der Foundation-Features sollte `NexumExtension` hauptsächlich orchestrieren. Discovery, Architekturvalidierung und Symfony-/Doctrine-Übersetzung sind getrennte Aufgaben. Der aktuelle `ArchitectureValidationPass` verspricht durch seinen Namen mehr, als die Prüfung auf Registry-Existenz tatsächlich leistet; entsprechend umbenennen oder mit klar umrissener Validierung versehen.

Fehler sollten Module mit ihren logischen Schlüsseln benennen und die Ursache zeigen. Bei Zyklen ist `billing → payment → billing` hilfreicher als nur die Klasse, an der der Zyklus erkannt wurde. Vollständige Class-Strings und Dateipfade bleiben ergänzende Details.

## Empfohlene Reihenfolge und Abnahmekriterien

1. **Bestehende Kernfehler absichern und korrigieren.** Deaktivierte private Module, verwaiste Ownership, doppelte Descriptor-Bereiche, relative Roots und gewöhnliche `Module.php`-/`Project.php`-Dateien bekommen gezielte Regressionstests. Service-Discovery wird mit nativem Symfony-Verhalten verglichen.
2. **Ein gemeinsames Architektur-Build-Ergebnis verwenden.** Discovery-Katalog und aktive Graphen unterscheiden; DI, Routing und Doctrine daraus ableiten. Deaktivierte und unbenutzte Module erzeugen keine Nexum-Registrierungen. Ein aktives Modul mit deaktivierter Dependency scheitert.
3. **Foundation aus dem Paket lösen.** Composer, Extension, RouteLoader und Konfiguration bereinigen. `nexum:architecture` im Kern erhalten. Minimal-App ohne Doctrine/Security/API sowie normalen Symfony-Code parallel testen.
4. **Flexible Discovery einführen.** Default-Struktur unverändert nutzbar lassen; mehrere Roots, Namespaces, Excludes und alternative Integrationsbereiche testen. Ownership muss eindeutig und unabhängig von Scan-Reihenfolge sein. Native Overrides dürfen weiterhin funktionieren.
5. **Tatsächliche Dependencies prüfen.** Direkte undeclared Dependency, direkter Zugriff trotz nur transitiver Deklaration, Shared→Private, Private→fremdes Project und legitime Referenzen als kleine Analyzer-Fixtures prüfen. Keine Modulzuordnung für externe Symfony-/Vendor-Klassen erfinden.
6. **Diagnostik und Kompatibilitätsmatrix vervollständigen.** Katalog, Aktivierung, Pfade, deklarierte/erkannte Dependencies und Integrationsbereiche ausgeben. Unterstützte PHP-/Symfony-Linien in einer reproduzierbaren CI-Matrix prüfen; ein reines Composer-Constraint ist noch kein Laufzeittest.

Dabei bleiben Projects Anwendungsbereiche mit Routen-/Host-/Modulzuordnung. Es gibt keinen Anlass, getrennte Container, Datenbanken oder Messenger-Laufzeiten einzuführen. Die vorhandenen Integrationstests bilden diese leichte Semantik bereits sinnvoll ab.
