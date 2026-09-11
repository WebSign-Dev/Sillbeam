<p align="center"><img src="../assets/nexum-logo.png" alt="Nexum – Build · Connect · Extend" width="520"></p>

# Search

`Nexum\Search` beschreibt provider-neutrale Suchanfragen und Treffer. Elasticsearch, OpenSearch, Meilisearch oder SQL bleiben austauschbare Implementierungen von `SearchProviderInterface`.

```php
$result = $search->search('products', new SearchQuery(
    text: 'modular framework',
    filters: ['language' => 'de', 'status' => ['published']],
    limit: 20,
    offset: 0,
));
```

`SearchQuery` begrenzt Text auf 1000 Zeichen, Limit auf 1–100 und Offset auf nichtnegative Werte. `SearchResult` enthält Treffer und Gesamtanzahl; `SearchHit` enthält ID, Score und skalare Felder.

Die Abstraktion definiert bewusst keine anbieterspezifische Query DSL, Facets, Highlighting oder Indexverwaltung. Solche Fähigkeiten gehören in spezialisierte Adapter oder zusätzliche Anwendungsschnittstellen.

Weiter: [Optionale Bausteine](../guides/optional-modules.md).
