<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Fehlerdiagnose

## Ein Module wird nicht gefunden

Prüfen:

1. Liegt der Descriptor exakt unter `src/Module/<Name>/<Name>Module.php`?
2. Entspricht Namespace und Class-String dem Composer-PSR-4-Mapping?
3. Ist `#[NexumModule]` auf der Klasse gesetzt?
4. Ist die Datei autoloadbar und frei von Ladefehlern?
5. Benötigt die Composer-Struktur einen `nexum.discovery.namespace`-Override?

Danach Cache neu bauen und `nexum:architecture` ausführen.

## Ein Service existiert nicht

- Descriptoren, Entities und DTOs sind absichtlich keine Services.
- Im Project Mode werden unzugeordnete Shared Modules nicht geladen.
- Ein deaktiviertes oder ungenutztes Modul wird nicht kompiliert.
- Symfony kann unbenutzte private Services optimieren; reale Verbraucher per Autowiring prüfen.

```bash
php bin/console debug:container 'App\\Module\\Billing\\Service\\InvoiceService'
```

## Eine Route fehlt

- Der einmalige `type: nexum`-Import muss existieren.
- Normale `src/Controller` benötigen weiterhin den Symfony-Standardimport.
- Im Project Mode ist der Routenname mit `<project-id>.` präfigiert.
- Das Module muss dem Project direkt oder transitiv zugeordnet sein.
- Exponierende Framework-APIs benötigen ihren eigenen `api.enabled`-Schalter und eine Project-Allowlist.

## Eine Route ist auf dem falschen Host nicht erreichbar

Das ist im Project Mode beabsichtigt. Nexum setzt exakte Host-Requirements. Prüfen:

```bash
php bin/console debug:router <project-id>.<route-name>
```

Der erste Project-Host ist canonical. Weitere Hosts akzeptieren eingehende Requests, werden aber nicht für absolute URL-Generierung gewählt.

## `Untrusted Host`

Dieser Fehler stammt aus Symfonys `trusted_hosts` und liegt vor Nexums Project-Auflösung. Beide Listen müssen zusammenpassen. Nexum lockert Symfonys Hostschutz nicht automatisch.

## Build-Fehler im Dependency-Graph

Typische Ursachen:

- Dependency-Class-String zeigt nicht auf einen gefundenen Descriptor.
- ein aktiver Graph benötigt ein deaktiviertes Module;
- Shared Module hängt von einem privaten Module ab;
- private Module verschiedener Projects hängen voneinander ab;
- Zyklus zwischen Modules;
- doppelte Shared- oder Project-ID.

Die Behebung erfolgt im Descriptor oder in Compile-time-Konfiguration, niemals durch Runtime-Mutation.

## Doctrine-Mapping fehlt

- `nexum.doctrine.enabled` muss aktiv sein.
- DoctrineBundle muss registriert sein.
- Entities liegen konventionell in einem `Entity/`-Verzeichnis innerhalb eines verwendeten Modules oder Projects.
- Reine PHP-Entity-Klassen bleiben auch bei deaktivierter Integration ladbar, werden dann aber nicht automatisch gemappt.

## API lässt sich nicht aktivieren

Die generische API benötigt Doctrine. User benötigt Doctrine und Security. MFA und Passkeys benötigen ebenfalls Security und Doctrine. Management-API benötigt Management, API und Security.

Die Fehlermeldungen werden beim Container-Build erzeugt, damit eine unvollständige Kombination nicht erst beim ersten Request auffällt.

## Cache reagiert nicht auf neue Controller

Nexum registriert Directory- und File-Resources für Container und Router getrennt. In Development sollte Symfony Änderungen erkennen. Bei produktionsähnlichem Cache den Cache bewusst neu bauen:

```bash
php bin/console cache:clear
php bin/console cache:warmup
```

Weitere Diagnosebefehle: [CLI-Referenz](../reference/cli.md).
