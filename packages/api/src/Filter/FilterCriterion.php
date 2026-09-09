<?php

declare(strict_types=1);

namespace Nexum\Api\Filter;

final readonly class FilterCriterion
{
    public function __construct(
        public string $field,
        public FilterOperator $operator,
        public mixed $value,
    ) {
    }
}
