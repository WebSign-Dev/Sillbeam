<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Filesystem

`Nexum\Filesystem` kapselt begrenzten Objektspeicher hinter `StorageInterface`. Die Anwendung arbeitet mit relativen sicheren Pfaden und kann zwischen lokalem Storage und Flysystem wechseln.

## Aktivierung und Verwendung

```yaml
nexum:
    filesystem:
        enabled: true
        root: '%kernel.project_dir%/storage/files'
        max_read_bytes: 10485760
```

```php
public function __construct(private StorageInterface $storage) {}

$this->storage->write('invoices/2026/42.pdf', $contents);
$contents = $this->storage->read('invoices/2026/42.pdf');
$exists = $this->storage->exists('invoices/2026/42.pdf');
$this->storage->delete('invoices/2026/42.pdf');
```

## Sicherheit

`SafePath` akzeptiert nur nichtleere relative Pfade. Absolute Pfade, NUL-Bytes, leere Segmente, `.` und `..` werden abgelehnt. `LocalStorage` erzeugt Verzeichnisse mit `0700` und versucht Dateien auf `0600` zu begrenzen. Reads oberhalb des konfigurierten Limits schlagen fehl.

`FlysystemStorage` wendet dieselbe Pfadprüfung und Größenbegrenzung auf einen injizierten `FilesystemOperator` an. Adapterfehler werden als `StorageException` normalisiert.

## Grenzen

- Keine öffentliche Uploadroute.
- Kein automatisches MIME- oder Virenscanning.
- Keine Versionierung oder Lifecycle Policy.
- Remote-Adapter und Credentials bleiben Anwendungskonfiguration.

Weiter: [Infrastruktur-Guide](../guides/infrastructure.md) und [Security-Checkliste](../operations/security-checklist.md).
