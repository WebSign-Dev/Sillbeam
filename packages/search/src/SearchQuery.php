<?php

declare(strict_types=1);

namespace Nexum\Search;

final readonly class SearchQuery
{
    /** @param array<string, scalar|list<scalar>> $filters */
    public function __construct(public string $text, public array $filters = [], public int $limit = 20, public int $offset = 0)
    {
        if ($limit < 1 || $limit > 100 || $offset < 0 || \strlen($text) > 1000) {
            throw new \InvalidArgumentException('Invalid search query bounds.');
        }
    }
}
