<?php

declare(strict_types=1);

namespace Nexum\Search;

final readonly class SearchResult
{
    /** @param list<SearchHit> $hits */
    public function __construct(public array $hits, public int $total)
    {
    }
}
