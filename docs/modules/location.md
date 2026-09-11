<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Location

`Nexum\Location` enthält kleine immutable Value Objects ohne Framework-Abhängigkeit.

```php
$coordinates = new Coordinates(48.137154, 11.576124);
$address = new PostalAddress('Marienplatz 1', '80331', 'München', 'DE');
```

`Coordinates` akzeptiert nur endliche WGS84-Werte: Latitude −90 bis 90, Longitude −180 bis 180. `PostalAddress` verlangt Straße, Postleitzahl und Ort sowie einen zweistelligen Uppercase-Country-Code.

Die Klassen führen kein Geocoding, keine Distanzberechnung und keine länderspezifische Postleitzahlvalidierung durch. Diese Aufgaben bleiben austauschbare Services der Anwendung.

Weiter: [Optionale Bausteine](../guides/optional-modules.md) und [öffentliche API](../reference/public-api.md).
