<?php

declare(strict_types=1);

namespace Nexum\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Nexum\Entity\Contract\SoftDeletableInterface;

final class SoftDeleteFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!is_a($targetEntity->getName(), SoftDeletableInterface::class, true) || !$targetEntity->hasField('deletedAt')) {
            return '';
        }

        $column = $this->getConnection()->quoteSingleIdentifier($targetEntity->getColumnName('deletedAt'));

        return \sprintf('%s.%s IS NULL', $targetTableAlias, $column);
    }
}
