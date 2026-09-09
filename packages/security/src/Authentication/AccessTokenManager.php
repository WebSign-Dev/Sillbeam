<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

use Nexum\Security\Contract\SubjectUserInterface;
use Psr\Clock\ClockInterface;

final readonly class AccessTokenManager
{
    public function __construct(
        private AccessTokenStoreInterface $store,
        private AccessTokenHasher $hasher,
        private ClockInterface $clock,
        private int $defaultTtl,
    ) {
    }

    public function issue(SubjectUserInterface $user, ?string $label = null): IssuedAccessToken
    {
        $issuedAt = \DateTimeImmutable::createFromInterface($this->clock->now());
        $expiresAt = $issuedAt->modify('+'.$this->defaultTtl.' seconds');
        $plainToken = 'nxm_'.rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $this->store->store(
            $user,
            $this->hasher->hash($plainToken),
            $label,
            $issuedAt,
            $expiresAt,
        );

        return new IssuedAccessToken($plainToken, $expiresAt);
    }

    public function revoke(#[\SensitiveParameter] string $plainToken): bool
    {
        return $this->store->revoke(
            $this->hasher->hash($plainToken),
            \DateTimeImmutable::createFromInterface($this->clock->now()),
        );
    }

    public function revokeAllForUser(SubjectUserInterface $user): int
    {
        return $this->store->revokeAllForUser(
            $user,
            \DateTimeImmutable::createFromInterface($this->clock->now()),
        );
    }
}
