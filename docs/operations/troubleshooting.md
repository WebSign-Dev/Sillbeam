# Fehlerdiagnose

- **Namespace nicht eindeutig:** `discovery.namespace` oder den Namespace je Root angeben; Composer-PSR-4 und `composer dump-autoload` prüfen.
- **Descriptor außerhalb der Grenze:** Module-/Project-Root konfigurieren oder Datei unter die erlaubte Grenze verschieben. Ein privates Modul braucht einen Project-Descriptor als physischen Eigentümer.
- **Überlappende Bereiche:** Descriptor-Verzeichnisse trennen. Fachliche Unterordner ohne eigenen Descriptor sind erlaubt.
- **Fehlende Dependency:** Direkt referenziertes Zielmodul in `dependencies` aufnehmen; Referenz in Datei/Zeile der Exception prüfen.
- **Deaktiviertes Ziel:** Abhängigkeit entfernen oder Ziel aktivieren. `enabled` und `active` sind verschiedene Zustände; mit Projects brauchen Shared Modules eine Zuordnung.
- **Fehlender Service:** Excludes, Environment-Attribute und aktive Zuordnung prüfen; native Overrides sind möglich. Mehrere Implementierungen brauchen einen expliziten Alias.
- **Unerwartete Route/Entity:** Explizite native Imports können Nexum-Ausschlüsse umgehen. Bei mehreren Projects Route-Namenspräfix und Hostfilter berücksichtigen.
- **Doctrine fehlt:** Bundle installieren/registrieren oder Nexum-Integration ausschalten. Das Core-Package braucht Doctrine nicht.
- **Geänderte Aktivierung ohne Effekt:** Build-Umgebungsvariablen verlangen Cache-Neubau. Dev-Ressourcen überwachen Quellverzeichnisse; Produktionscache wird im Deployment neu gebaut.

Diagnose: `nexum:architecture --format=json`, `debug:container`, `debug:router`; siehe [CLI](../reference/cli.md).
