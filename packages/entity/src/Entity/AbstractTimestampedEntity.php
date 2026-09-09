<?php

declare(strict_types=1);

namespace Nexum\Entity\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Contract\TimestampedInterface;

#[ORM\MappedSuperclass]
abstract class AbstractTimestampedEntity extends AbstractEntity implements TimestampedInterface
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function initializeTimestamps(\DateTimeImmutable $now): void
    {
        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
    }

    public function touch(\DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }
}
