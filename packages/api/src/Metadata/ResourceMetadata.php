<?php

declare(strict_types=1);

namespace Nexum\Api\Metadata;

use Nexum\Api\Operation\Operation;
use Nexum\Api\Scope\ResourceScope;
use Nexum\Entity\Contract\EntityInterface;

final readonly class ResourceMetadata
{
    /**
     * @param class-string<EntityInterface> $entity
     * @param list<Operation>               $operations
     * @param list<string>                  $writableFields
     * @param list<string>                  $requiredFields
     * @param array<string, list<string>>   $filters
     * @param list<string>                  $sortableFields
     * @param array<string, 'asc'|'desc'>   $defaultSort
     * @param array<string, mixed>          $normalizationContext
     * @param array<string, mixed>          $denormalizationContext
     */
    public function __construct(
        public string $entity,
        public array $operations,
        public array $writableFields,
        public array $requiredFields,
        public array $filters,
        public array $sortableFields,
        public array $defaultSort,
        public int $defaultPageSize,
        public int $maxPageSize,
        public ?string $permissionPrefix,
        public ResourceScope $scope,
        public ?string $scopeField,
        public array $normalizationContext,
        public array $denormalizationContext,
    ) {
    }

    public function supports(Operation $operation): bool
    {
        return \in_array($operation, $this->operations, true);
    }
}
