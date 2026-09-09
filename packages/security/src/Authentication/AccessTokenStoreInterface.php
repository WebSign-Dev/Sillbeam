<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

use Nexum\Security\Contract\SubjectUserInterface;

interface AccessTokenStoreInterface
{
    public function store(
        SubjectUserInterface $user,
        string $tokenHash,
        ?string $label,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ): void;

    public function findActiveUser(string $tokenHash, \DateTimeImmutable $now): ?SubjectUserInterface;

    public function revoke(string $tokenHash, \DateTimeImmutable $revokedAt): bool;

    public function revokeAllForUser(SubjectUserInterface $user, \DateTimeImmutable $revokedAt): int;
}
