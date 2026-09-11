<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\AbstractEntity;
use Nexum\Security\Contract\SubjectUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_mfa_challenge')]
#[ORM\Index(name: 'IDX_NEXUM_MFA_CHALLENGE_USER', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_NEXUM_MFA_CHALLENGE_EXPIRY', columns: ['expiresAt'])]
final class MfaChallenge extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: SubjectUserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private SubjectUserInterface $user;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private int $failedAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    public function __construct(SubjectUserInterface $user, \DateTimeImmutable $expiresAt)
    {
        parent::__construct();
        $this->user = $user;
        $this->expiresAt = $expiresAt;
    }

    public function getUser(): SubjectUserInterface
    {
        return $this->user;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isUsableAt(\DateTimeImmutable $now, int $maxAttempts): bool
    {
        return null === $this->consumedAt && $this->expiresAt > $now && $this->failedAttempts < $maxAttempts;
    }

    public function fail(): void
    {
        ++$this->failedAttempts;
    }

    public function consume(\DateTimeImmutable $now): void
    {
        $this->consumedAt ??= $now;
    }
}
