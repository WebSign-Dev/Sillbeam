<?php

declare(strict_types=1);

namespace Nexum\Search;

interface SearchProviderInterface
{
    public function search(string $index, SearchQuery $query): SearchResult;
}
