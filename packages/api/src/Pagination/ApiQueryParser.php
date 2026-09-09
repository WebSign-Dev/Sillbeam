<?php

declare(strict_types=1);

namespace Nexum\Api\Pagination;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Filter\FilterCriterion;
use Nexum\Api\Filter\FilterOperator;
use Nexum\Api\Metadata\ResourceMetadata;
use Symfony\Component\HttpFoundation\Request;

final class ApiQueryParser
{
    public function parse(Request $request, ResourceMetadata $metadata): ApiQuery
    {
        $query = $request->query->all();
        $page = $query['page'] ?? [];

        if (!\is_array($page)) {
            throw ApiProblemException::badRequest('The "page" parameter must use bracket syntax.');
        }

        $after = $this->optionalString($page['after'] ?? null, 'page[after]');
        $modeValue = $this->optionalString($page['mode'] ?? null, 'page[mode]');

        try {
            $mode = null !== $after
                ? PaginationMode::CURSOR
                : PaginationMode::from($modeValue ?? PaginationMode::PAGE->value);
        } catch (\ValueError) {
            throw ApiProblemException::badRequest('page[mode] must be either "page" or "cursor".');
        }

        $number = $this->positiveInteger($page['number'] ?? 1, 'page[number]');
        $size = $this->positiveInteger($page['size'] ?? $metadata->defaultPageSize, 'page[size]');

        if ($size > $metadata->maxPageSize) {
            throw ApiProblemException::badRequest(\sprintf('page[size] must not exceed %d.', $metadata->maxPageSize));
        }

        if (PaginationMode::CURSOR === $mode && (isset($page['number']) || isset($query['sort']))) {
            throw ApiProblemException::badRequest('Cursor pagination cannot be combined with page[number] or sort.');
        }

        return new ApiQuery(
            mode: $mode,
            page: $number,
            size: $size,
            after: $after,
            filters: $this->parseFilters($query['filter'] ?? [], $metadata),
            sort: PaginationMode::CURSOR === $mode ? ['id' => 'asc'] : $this->parseSort($query['sort'] ?? null, $metadata),
        );
    }

    /** @return list<FilterCriterion> */
    private function parseFilters(mixed $rawFilters, ResourceMetadata $metadata): array
    {
        if (!\is_array($rawFilters)) {
            throw ApiProblemException::badRequest('The "filter" parameter must use bracket syntax.');
        }

        $criteria = [];

        foreach ($rawFilters as $field => $rawCriterion) {
            if (!\is_string($field) || !isset($metadata->filters[$field])) {
                throw ApiProblemException::badRequest(\sprintf('Filtering by "%s" is not allowed.', (string) $field), 'filter_not_allowed');
            }

            $operations = \is_array($rawCriterion) ? $rawCriterion : ['eq' => $rawCriterion];

            foreach ($operations as $operatorName => $value) {
                if (!\is_string($operatorName) || !\in_array($operatorName, $metadata->filters[$field], true)) {
                    throw ApiProblemException::badRequest(\sprintf('Filter operator "%s" is not allowed for "%s".', (string) $operatorName, $field), 'filter_operator_not_allowed');
                }

                try {
                    $operator = FilterOperator::from($operatorName);
                } catch (\ValueError) {
                    throw ApiProblemException::badRequest(\sprintf('Unknown filter operator "%s".', $operatorName));
                }

                $criteria[] = new FilterCriterion($field, $operator, $value);
            }
        }

        return $criteria;
    }

    /** @return array<string, 'asc'|'desc'> */
    private function parseSort(mixed $rawSort, ResourceMetadata $metadata): array
    {
        if (null === $rawSort || '' === $rawSort) {
            return $metadata->defaultSort;
        }

        if (!\is_string($rawSort)) {
            throw ApiProblemException::badRequest('The "sort" parameter must be a comma-separated string.');
        }

        $sort = [];

        foreach (explode(',', $rawSort) as $entry) {
            $entry = trim($entry);
            $direction = str_starts_with($entry, '-') ? 'desc' : 'asc';
            $field = ltrim($entry, '-');

            if ('' === $field || !\in_array($field, $metadata->sortableFields, true)) {
                throw ApiProblemException::badRequest(\sprintf('Sorting by "%s" is not allowed.', $field), 'sort_not_allowed');
            }

            $sort[$field] = $direction;
        }

        return $sort;
    }

    private function positiveInteger(mixed $value, string $parameter): int
    {
        if (!\is_scalar($value) || false === filter_var($value, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            throw ApiProblemException::badRequest(\sprintf('%s must be a positive integer.', $parameter));
        }

        return (int) $value;
    }

    private function optionalString(mixed $value, string $parameter): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value) || '' === $value) {
            throw ApiProblemException::badRequest(\sprintf('%s must be a non-empty string.', $parameter));
        }

        return $value;
    }
}
