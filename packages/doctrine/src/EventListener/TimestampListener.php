<?php

declare(strict_types=1);

namespace Nexum\Doctrine\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Nexum\Entity\Contract\TimestampedInterface;
use Psr\Clock\ClockInterface;

final readonly class TimestampListener
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof TimestampedInterface) {
            $entity->initializeTimestamps($this->clock->now());
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $now = $this->clock->now();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof TimestampedInterface) {
                continue;
            }

            $entity->touch($now);
            $metadata = $entityManager->getClassMetadata($entity::class);
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
        }
    }
}
