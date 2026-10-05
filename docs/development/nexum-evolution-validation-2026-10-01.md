# Verifikation der Nexum-Evolution

Stand: 1. Oktober 2026, PHP 8.5.4.

| Prüfung | Ergebnis |
|---|---|
| Gesamtsuite mit Symfony 8.1.6 (Repository-Lockfile) | 65 Tests, 240 Assertions bestanden |
| Gesamtsuite mit Symfony 7.4.20 (temporäre Abhängigkeiten) | 65 Tests, 240 Assertions bestanden |
| Gesamtsuite mit Symfony 8.0.15 (temporäre Abhängigkeiten) | 65 Tests, 240 Assertions bestanden |
| PHPStan | keine Fehler |
| Deptrac | keine Verletzungen, Warnungen oder Fehler |
| CS-Fixer | 0 von 118 Dateien zu ändern |
| Composer validate --strict | gültig |
| git diff --check | sauber |
| Echte Flex-Installation in frischem Verbraucherprojekt | Recipe und Modulroute funktionieren ohne Doctrine |

Ein unabhängiger Reviewer fand drei relevante Fehler bei Konfigurationsmerge, Ausschlüssen in Elternimporten und Project/Module-Ownership. Alle wurden mit zuvor fehlschlagenden Regressionstests korrigiert. Die zusätzliche Invalidierung des Routencaches bei Architekturkonfiguration/Composer-Änderungen ist ebenfalls durch einen Regressionstest abgedeckt.

PHP 8.4 ist in der CI-Matrix konfiguriert, wurde lokal mangels Interpreter nicht ausgeführt. Die Remote-CI wurde in dieser Sitzung nicht gestartet. Die Composer-Verbraucherinstallation verwendet die aktuell kompatiblen Pakete, unabhängig von den Entwicklungspaketen des Repositorys.

Reproduzierbare Befehle: siehe [Testing](testing.md). Änderungen liegen lokal auf `codex/nexum-evolution`; es erfolgten kein Push, Merge oder Deployment. Die entfernte Foundation erfordert die [Migration](../migration/modular-monolith.md).
