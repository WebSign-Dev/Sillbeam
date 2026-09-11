<?php

declare(strict_types=1);

namespace Nexum\Api\Metadata;

use Nexum\Api\Attribute\ApiResource;
use Nexum\Api\Filter\FilterOperator;
use Nexum\Api\Operation\Operation;
use Nexum\Api\Scope\ResourceScope;
use Nexum\Entity\Contract\EntityInterface;

final class ResourceMetadataFactory
{
    /** @var array<class-string, ResourceMetadata> */
    private array $cache = [];

    /** @param class-string $controller */
    public function forController(string $controller): ResourceMetadata
    {
        return $this->cache[$controller] ??= $this->create($controller);
    }

    /** @param class-string $controller */
    private function create(string $controller): ResourceMetadata
    {
        $reflection = new \ReflectionClass($controller);
        $attribute = $reflection->getAttributes(ApiResource::class)[0] ?? null;

        if (null === $attribute) {
            throw new \LogicException(\sprintf('API controller "%s" must declare #[ApiResource].', $controller));
        }

        $resource = $attribute->newInstance();

        if (!is_subclass_of($resource->entity, EntityInterface::class)) {
            throw new \LogicException(\sprintf('API resource "%s" must implement %s.', $resource->entity, EntityInterface::class));
        }

        if ($resource->defaultPageSize < 1 || $resource->maxPageSize < $resource->defaultPageSize) {
            throw new \LogicException('API page sizes must be positive and maxPageSize must not be smaller than defaultPageSize.');
        }

        $operations = [] === $resource->operations ? Operation::all() : $resource->operations;

        foreach ($operations as $operation) {
            if (!$operation instanceof Operation) {
                throw new \LogicException('ApiResource operations must contain Operation enum cases only.');
            }
        }

        foreach ($resource->requiredFields as $field) {
            if (!\in_array($field, $resource->writableFields, true)) {
                throw new \LogicException(\sprintf('Required API field "%s" must also be writable.', $field));
            }
        }

        foreach ($resource->filters as $field => $operators) {
            if (!\is_string($field) || [] === $operators) {
                throw new \LogicException('ApiResource filters must map field names to at least one operator.');
            }

            foreach ($operators as $operator) {
                if (!\is_string($operator) || null === FilterOperator::tryFrom($operator)) {
                    throw new \LogicException(\sprintf('Unknown API filter operator "%s" for "%s".', (string) $operator, $field));
                }
            }
        }

        if (null !== $resource->permissionPrefix && !preg_match('/^[a-z][a-z0-9_.-]*$/', $resource->permissionPrefix)) {
            throw new \LogicException(\sprintf('Invalid API permission prefix "%s".', $resource->permissionPrefix));
        }

        if (ResourceScope::SUBJECT === $resource->scope && (null === $resource->scopeField || '' === trim($resource->scopeField))) {
            throw new \LogicException('Subject-scoped API resources must configure scopeField.');
        }
        if (ResourceScope::SUBJECT !== $resource->scope && null !== $resource->scopeField) {
            throw new \LogicException('Only subject-scoped API resources may configure scopeField.');
        }

        foreach ($resource->defaultSort as $field => $direction) {
            if (!\in_array($field, $resource->sortableFields, true) || !\in_array($direction, ['asc', 'desc'], true)) {
                throw new \LogicException(\sprintf('Invalid default API sort "%s".', $field));
            }
        }

        return new ResourceMetadata(
            entity: $resource->entity,
            operations: $operations,
            writableFields: array_values(array_unique($resource->writableFields)),
            requiredFields: array_values(array_unique($resource->requiredFields)),
            filters: $resource->filters,
            sortableFields: array_values(array_unique($resource->sortableFields)),
            defaultSort: $resource->defaultSort,
            defaultPageSize: $resource->defaultPageSize,
            maxPageSize: $resource->maxPageSize,
            permissionPrefix: $resource->permissionPrefix,
            scope: $resource->scope,
            scopeField: $resource->scopeField,
            normalizationContext: $resource->normalizationContext,
            denormalizationContext: $resource->denormalizationContext,
        );
    }
}
