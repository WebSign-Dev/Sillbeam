<?php

declare(strict_types=1);

namespace Nexum\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Contract\SoftDeletableInterface;

#[ORM\MappedSuperclass]
abstract class AbstractSoftDeletableEntity extends AbstractTimestampedEntity implements SoftDeletableInterface
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $deletedAt = null;

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function markDeleted(\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }
}
