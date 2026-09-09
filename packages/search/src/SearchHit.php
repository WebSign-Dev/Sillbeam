<?php

declare(strict_types=1);

namespace Nexum\Search;

final readonly class SearchHit
{
    /** @param array<string, scalar|null> $fields */
    public function __construct(public string $id, public float $score, public array $fields = [])
    {
    }
}
