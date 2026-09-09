<?php

declare(strict_types=1);

namespace Nexum\Api\Pagination;

use Nexum\Api\Filter\FilterCriterion;

final readonly class ApiQuery
{
    /**
     * @param list<FilterCriterion>       $filters
     * @param array<string, 'asc'|'desc'> $sort
     */
    public function __construct(
        public PaginationMode $mode,
        public int $page,
        public int $size,
        public ?string $after,
        public array $filters,
        public array $sort,
    ) {
    }
}
