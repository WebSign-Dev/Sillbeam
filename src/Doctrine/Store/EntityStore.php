<?php

declare(strict_types=1);

namespace Nexum\Doctrine\Store;

use Doctrine\ORM\EntityManagerInterface;
use Nexum\Entity\Contract\EntityInterface;
use Nexum\Entity\Contract\SoftDeletableInterface;
use Psr\Clock\ClockInterface;

final readonly class EntityStore implements EntityStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    public function persist(EntityInterface $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function delete(EntityInterface $entity): void
    {
        if ($entity instanceof SoftDeletableInterface) {
            $entity->markDeleted($this->clock->now());

            return;
        }

        $this->entityManager->remove($entity);
    }

    public function restore(SoftDeletableInterface $entity): void
    {
        $entity->restore();
    }

    public function purge(EntityInterface $entity): void
    {
        $this->entityManager->remove($entity);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function transactional(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(fn (): mixed => $operation($this));
    }
}
