<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

use Nexum\Security\Contract\SubjectUserInterface;

final class NullAccessTokenStore implements AccessTokenStoreInterface
{
    public function store(
        SubjectUserInterface $user,
        string $tokenHash,
        ?string $label,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ): void {
        throw new \LogicException('No Nexum access-token storage adapter is installed. Install nexum/user or provide AccessTokenStoreInterface.');
    }

    public function findActiveUser(string $tokenHash, \DateTimeImmutable $now): ?SubjectUserInterface
    {
        return null;
    }

    public function revoke(string $tokenHash, \DateTimeImmutable $revokedAt): bool
    {
        return false;
    }

    public function revokeAllForUser(SubjectUserInterface $user, \DateTimeImmutable $revokedAt): int
    {
        return 0;
    }
}
