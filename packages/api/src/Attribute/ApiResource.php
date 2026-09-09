<?php

declare(strict_types=1);

namespace Nexum\Api\Attribute;

use Nexum\Api\Operation\Operation;
use Nexum\Api\Scope\ResourceScope;
use Nexum\Entity\Contract\EntityInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class ApiResource
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
        public array $operations = [],
        public array $writableFields = [],
        public array $requiredFields = [],
        public array $filters = [],
        public array $sortableFields = ['id'],
        public array $defaultSort = ['id' => 'asc'],
        public int $defaultPageSize = 20,
        public int $maxPageSize = 100,
        public ?string $permissionPrefix = null,
        public ResourceScope $scope = ResourceScope::DENY,
        public ?string $scopeField = null,
        public array $normalizationContext = [],
        public array $denormalizationContext = [],
    ) {
    }
}
