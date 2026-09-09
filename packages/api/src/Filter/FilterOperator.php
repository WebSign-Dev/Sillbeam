<?php

declare(strict_types=1);

namespace Nexum\Api\Filter;

enum FilterOperator: string
{
    case EQUALS = 'eq';
    case NOT_EQUALS = 'neq';
    case CONTAINS = 'contains';
    case GREATER_THAN = 'gt';
    case GREATER_THAN_OR_EQUAL = 'gte';
    case LESS_THAN = 'lt';
    case LESS_THAN_OR_EQUAL = 'lte';
    case IN = 'in';
    case IS_NULL = 'is_null';
}
