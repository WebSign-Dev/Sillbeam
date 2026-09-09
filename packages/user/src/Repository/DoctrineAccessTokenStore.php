<?php

declare(strict_types=1);

namespace Nexum\User\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nexum\Security\Authentication\AccessTokenStoreInterface;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\User\Entity\ApiAccessToken;

final readonly class DoctrineAccessTokenStore implements AccessTokenStoreInterface
{
    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function store(
        SubjectUserInterface $user,
        string $tokenHash,
        ?string $label,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ): void {
        $manager = $this->manager();
        $manager->persist(new ApiAccessToken($user, $tokenHash, $label, $issuedAt, $expiresAt));
        $manager->flush();
    }

    public function findActiveUser(string $tokenHash, \DateTimeImmutable $now): ?SubjectUserInterface
    {
        $token = $this->manager()->createQueryBuilder()
            ->select('token', 'user')
            ->from(ApiAccessToken::class, 'token')
            ->innerJoin('token.user', 'user')
            ->where('token.tokenHash = :hash')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();

        return $token instanceof ApiAccessToken && $token->isActiveAt($now) ? $token->getUser() : null;
    }

    public function revoke(string $tokenHash, \DateTimeImmutable $revokedAt): bool
    {
        $manager = $this->manager();
        $token = $manager->getRepository(ApiAccessToken::class)->findOneBy(['tokenHash' => $tokenHash]);

        if (!$token instanceof ApiAccessToken || !$token->isActiveAt($revokedAt)) {
            return false;
        }

        $token->revoke($revokedAt);
        $manager->flush();

        return true;
    }

    public function revokeAllForUser(SubjectUserInterface $user, \DateTimeImmutable $revokedAt): int
    {
        $manager = $this->manager();
        $tokens = $manager->getRepository(ApiAccessToken::class)->findBy(['user' => $user]);
        $revoked = 0;

        foreach ($tokens as $token) {
            if (!$token->isActiveAt($revokedAt)) {
                continue;
            }
            $token->revoke($revokedAt);
            ++$revoked;
        }

        if ($revoked > 0) {
            $manager->flush();
        }

        return $revoked;
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass(ApiAccessToken::class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine ORM manager found for Nexum access tokens.');
        }

        return $manager;
    }
}
