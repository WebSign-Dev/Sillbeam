# Optionale Fachmodule

Die optional nutzbaren Module enthalten absichtlich kleine öffentliche Verträge:

- **search**: begrenzte Suchanfragen und provider-neutrale Treffer
- **notification**: explizite Channel-Auswahl; unbekannte Channels schlagen fehl
- **location**: validierte Koordinaten und Postanschriften
- **payment**: Integer-Minor-Units, Währung und verpflichtende Idempotency Keys
- **qrcode**: begrenzte SVG-Erzeugung ohne Dateisystemzugriff
- **reporting**: JSON und CSV; CSV-Formeln werden gegen Spreadsheet Injection neutralisiert

Provider-SDKs gehören in separate Adapter-Pakete außerhalb des Framework-Kerns. Dadurch zieht eine Anwendung beispielsweise Stripe oder OpenSearch nur dann ein, wenn sie den Anbieter tatsächlich verwendet.
