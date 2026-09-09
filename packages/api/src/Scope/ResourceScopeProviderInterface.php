<?php

declare(strict_types=1);

namespace Nexum\Api\Scope;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Nexum\Api\Metadata\ResourceMetadata;
use Nexum\Api\Operation\Operation;
use Nexum\Entity\Contract\EntityInterface;

interface ResourceScopeProviderInterface
{
    /** @param ClassMetadata<object> $classMetadata */
    public function restrict(QueryBuilder $builder, ClassMetadata $classMetadata, ResourceMetadata $metadata, Operation $operation): void;

    /** @param ClassMetadata<object> $classMetadata */
    public function initialize(EntityInterface $resource, ClassMetadata $classMetadata, ResourceMetadata $metadata): void;
}
