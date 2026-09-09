<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private AccessTokenStoreInterface $store,
        private AccessTokenHasher $hasher,
        private ClockInterface $clock,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->store->findActiveUser(
            $this->hasher->hash($accessToken),
            \DateTimeImmutable::createFromInterface($this->clock->now()),
        );

        if (null === $user) {
            throw new BadCredentialsException('Invalid access token.');
        }

        return new UserBadge($user->getSubjectIdentifier(), static fn () => $user);
    }
}
