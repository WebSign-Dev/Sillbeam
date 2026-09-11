<?php

declare(strict_types=1);

namespace Nexum\Entity\Contract;

interface TimestampedInterface extends EntityInterface
{
    public function getCreatedAt(): ?\DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function initializeTimestamps(\DateTimeImmutable $now): void;

    public function touch(\DateTimeImmutable $now): void;
}
