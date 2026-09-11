<?php

declare(strict_types=1);

namespace Nexum\Doctrine\Store;

use Nexum\Entity\Contract\EntityInterface;
use Nexum\Entity\Contract\SoftDeletableInterface;

interface EntityStoreInterface
{
    public function persist(EntityInterface $entity): void;

    public function delete(EntityInterface $entity): void;

    public function restore(SoftDeletableInterface $entity): void;

    public function purge(EntityInterface $entity): void;

    public function flush(): void;

    /**
     * @template T
     *
     * @param callable(self): T $operation
     *
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
