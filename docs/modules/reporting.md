<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Reporting

`Nexum\Reporting` stellt ein einfaches tabellarisches Reportmodell und Renderer für CSV und JSON bereit. Alle Klassen funktionieren ohne Symfony-Kernel.

```php
$report = new Report(
    columns: ['number', 'total'],
    rows: [
        ['number' => 'INV-42', 'total' => 1299],
    ],
);

$csv = (new CsvReportRenderer())->render($report);
```

Spalten müssen nichtleer und eindeutig sein. Fehlende Row-Werte werden beim CSV-Rendering leer ausgegeben. `CsvReportRenderer` schützt Zellen, die mit `=`, `+`, `-` oder `@` beginnen, vor Spreadsheet-Formula-Injection. Der temporäre Stream verwendet bis zu 2 MiB RAM und weicht danach auf PHPs Temp-Mechanismus aus.

`JsonReportRenderer` liefert `columns` und `rows` als JSON und meldet Encoderfehler über `JSON_THROW_ON_ERROR`. Eigene Renderer implementieren `mediaType()` und `render()`.

Große Streamingexporte, PDF-Layouts und asynchrone Generierung gehören in spezialisierte Anwendungskomponenten.

Weiter: [Optionale Bausteine](../guides/optional-modules.md).
