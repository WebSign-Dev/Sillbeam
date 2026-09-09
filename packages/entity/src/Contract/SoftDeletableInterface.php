<?php

declare(strict_types=1);

namespace Nexum\Entity\Contract;

interface SoftDeletableInterface extends EntityInterface
{
    public function getDeletedAt(): ?\DateTimeImmutable;

    public function markDeleted(\DateTimeImmutable $deletedAt): void;

    public function restore(): void;
}
