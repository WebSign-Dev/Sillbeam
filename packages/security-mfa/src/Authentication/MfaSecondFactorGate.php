<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\Authentication;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Nexum\Security\Authentication\SecondFactorChallenge;
use Nexum\Security\Authentication\SecondFactorGateInterface;
use Nexum\Security\Contract\SubjectUserInterface;
use Nexum\Security\Mfa\Entity\MfaChallenge;
use Nexum\Security\Mfa\Entity\TotpEnrollment;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class MfaSecondFactorGate implements SecondFactorGateInterface
{
    public function __construct(private ManagerRegistry $registry, private ClockInterface $clock, private int $challengeTtl)
    {
    }

    public function begin(UserInterface $user): ?SecondFactorChallenge
    {
        if (!$user instanceof SubjectUserInterface) {
            throw new \LogicException('MFA requires a stable Nexum subject.');
        }
        $manager = $this->registry->getManagerForClass(TotpEnrollment::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine ORM manager is configured for Nexum MFA.');
        }

        return $manager->wrapInTransaction(function (EntityManagerInterface $manager) use ($user): ?SecondFactorChallenge {
            $manager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $enrollment = $manager->getRepository(TotpEnrollment::class)->findOneBy(['user' => $user]);
            if (!$enrollment instanceof TotpEnrollment || !$enrollment->isConfirmed()) {
                return null;
            }

            $now = \DateTimeImmutable::createFromInterface($this->clock->now());
            $challenge = new MfaChallenge($user, $now->modify('+'.$this->challengeTtl.' seconds'));
            $manager->persist($challenge);

            return new SecondFactorChallenge($challenge->getId()->toRfc4122(), ['totp', 'recovery_code'], $challenge->expiresAt());
        });
    }
}
