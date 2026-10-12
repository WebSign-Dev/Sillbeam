# Paketkompatibilität und Installation — 2026-10-10

> Historical, completed verification snapshot. Version counts and recipe details describe that run, not current installation instructions. See the [current release notes](../releases/0.1.0.md) and [Flex guide](../guides/flex-recipe.md).
Ausgangspunkt ist die technische Stabilisierung in `57bfe48`. Die Prüfung verwendet echte PHP-Laufzeiten und unabhängige Composer-Auflösungen in temporären, getrennten Verzeichnissen. Historische private GitHub-CI-Läufe wurden nicht als Fehlernachweis herangezogen.

## Unterstützte und tatsächlich geprüfte Versionen

`composer.json` erlaubt PHP `^8.4` und die Symfony-Komponenten `^7.4 || ^8.0`. Die geprüften Versionslinien sind PHP 8.4/8.5 mit Symfony 7.4/8.0/8.1. Welche Patchversion installierbar ist, bestimmt Composer einschließlich der Anforderungen aller Dependencies. Es wurde kein Platform-Override und kein `--ignore-platform-reqs` verwendet.

| PHP-Laufzeit | Symfony FrameworkBundle | Vollständige Qualitätssuite | 12 manuelle Consumer-Installationen | Flex ohne/mit Doctrine |
| --- | --- | --- | --- | --- |
| 8.4.26 | 7.4.20 | bestanden | bestanden | bestanden |
| 8.4.26 | 8.0.15 | bestanden | bestanden | bestanden |
| 8.4.26 | 8.1.8 | bestanden | bestanden | bestanden |
| 8.5.11 | 7.4.20 | bestanden | bestanden | bestanden |
| 8.5.11 | 8.0.15 | bestanden | bestanden | bestanden |
| 8.5.11 | 8.1.8 | bestanden | bestanden | bestanden |

Auch transitive Symfony-Komponenten liegen jeweils auf der gewählten Versionslinie. Contracts, Polyfills und Flex besitzen unabhängige Versionsnummern und werden nicht auf 7.4/8.0/8.1 gezwungen. Geprüfte optionale Dependencies: DoctrineBundle 3.3.2 und ORM 3.7.5. AST-Parser: 5.9.0; PHPUnit: 12.5.38. Andere Patchstände und spätere, durch die Composer-Constraints erlaubte Versionen wurden damit nicht automatisch getestet.

Je Matrix-Zelle bestanden PHPUnit (74 Tests / 263 Assertions), PHPStan, Deptrac und Code-Style sowie `composer validate --strict` und `composer check-platform-reqs`. Deptrac meldet 0 Verstöße, Warnungen und Fehler; externe Dependencies bleiben außerhalb der eigenen Layers. Die Container hatten zunächst 128 MiB PHP-Speicher, wodurch PHPStan abbrach; mit expliziten 512 MiB bestanden alle Läufe. Diese Einstellung ist auch in CI hinterlegt und keine Runtime-Anforderung von Sillbeam.

## Unabhängige Verbraucher

`composer test:consumer` kopiert ausschließlich Source und Konfiguration der sechs Szenarien in frische Anwendungen: Symfony-only, Modules-only, Project-only, Single-Project, Multi-Project und Hybrid. Jedes Szenario erhält eine eigene Installation ohne beziehungsweise mit Doctrine. Sillbeam wird aus einem lokalen Path-Repository **kopiert**, nicht über den Root-Autoloader oder einen Vendor-Symlink verwendet. Jeder Consumer startet ohne Lockfile, verwendet reguläre stabile Composer-Auflösung und besitzt seinen eigenen Vendor-Autoloader. Root-Dev-Dependencies und `Sillbeam\Tests` werden ausdrücklich ausgeschlossen.

Jede der 72 manuellen Matrix-Installationen wird in getrennten PHP-Prozessen mit dem normalen Symfony-Kernel und dem optionalen SillbeamKernel geprüft: insgesamt 144 erfolgreiche Kernel-Profile. Der Test prüft aktive Discovery-Graphen, Autowiring durch tatsächliche HTTP-Antworten, Routen/Hostgrenzen, die beiden Sillbeam-CLI-Kommandos und bei Doctrine native sowie Modul-/Project-Mappings mit Persistenz über denselben EntityManager. Ein tatsächlicher Consumer-Build lehnt zusätzlich aktive Referenzen auf deaktivierten Modulcode ab. Alle bestehenden weiteren Dependency-Regeln bleiben durch die vollständige PHPUnit-Suite abgedeckt.

Der Flex-Test installiert die vorbereitete Recipe über einen temporären lokalen HTTP-Endpoint. Er prüft Bundle-Registrierung, genau die beiden Recipe-Konfigurationsdateien, `symfony.lock`, Modulroute und beide Kernelvarianten. Im Doctrine-Profil wird außerdem das Modul-Mapping geprüft. Alle zwölf Recipe-Installationen der Matrix bestanden. Das beweist die vorbereitete Recipe, nicht ihre Verfügbarkeit bei Recipes Contrib; Paket und Recipe bleiben unveröffentlicht.

## Reproduktion und CI

Die folgenden Befehle **in einer wegwerfbaren Kopie** unter der gewünschten echten PHP-Laufzeit ausführen. `prepare-compatibility.php` verändert dort bewusst die Composer-Anforderungen. Für jede der drei Symfony-Linien und beide PHP-Versionen wiederholen:

```bash
export SILLBEAM_SYMFONY_VERSION='7.4.*' # alternativ 8.0.* oder 8.1.*
php tools/prepare-compatibility.php
composer update --with-all-dependencies --no-scripts --no-plugins --no-interaction --prefer-dist
composer validate --strict
composer check-platform-reqs
composer test
composer analyse
composer architecture
composer cs:check
composer test:consumer
composer test:flex-recipe
SILLBEAM_TEST_DOCTRINE=1 composer test:flex-recipe
```

PHP benötigt für die Suite unter anderem XML/DOM, mbstring und PDO SQLite; der Analyzer erhält 512 MiB Speicher. Installationstests benötigen Composer-Netzwerkzugriff, Flex zusätzlich einen lokalen HTTP-Port. Ohne `SILLBEAM_SYMFONY_VERSION` testet `composer test:consumer` die regulär aufgelösten Versionen des aktuellen Laufzeitsystems. Die CI verwendet dieselbe Vorbereitung und dieselben Tests für alle sechs Kombinationen. GitHub-Workflows wurden hier nicht gestartet; nach öffentlicher Freigabe können sie unabhängig erneut laufen.

## Ergebnis

Keine produktiven Kompatibilitäts- oder Integrationsfehler reproduziert; Produktionscode und Runtime-Constraints bleiben unverändert. Ergänzt wurden ausschließlich die erforderlichen unabhängigen Installationsprüfungen, reproduzierbare Versionsauswahl und CI-Abdeckung. Keine offenen technischen Blocker für die sechs geprüften Kombinationen. Es erfolgten keine Veröffentlichung und kein Push.
