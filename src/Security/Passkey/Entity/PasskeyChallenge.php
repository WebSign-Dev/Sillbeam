<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\AbstractEntity;
use Nexum\Security\Contract\SubjectUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_passkey_challenge')]
#[ORM\Index(name: 'IDX_NEXUM_PASSKEY_CHALLENGE_EXPIRY', columns: ['expiresAt'])]
#[ORM\Index(name: 'IDX_NEXUM_PASSKEY_CHALLENGE_USER', columns: ['user_id'])]
final class PasskeyChallenge extends AbstractEntity
{
    public const REGISTRATION = 'registration';
    public const AUTHENTICATION = 'authentication';

    #[ORM\Column(length: 20)]
    private string $ceremony;

    #[ORM\ManyToOne(targetEntity: SubjectUserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'CASCADE')]
    private ?SubjectUserInterface $user;

    #[ORM\Column(type: Types::TEXT)]
    private string $optionsJson;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private int $failedAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    public function __construct(string $ceremony, ?SubjectUserInterface $user, string $optionsJson, \DateTimeImmutable $expiresAt)
    {
        parent::__construct();
        $this->ceremony = $ceremony;
        $this->user = $user;
        $this->optionsJson = $optionsJson;
        $this->expiresAt = $expiresAt;
    }

    public function isFor(string $ceremony): bool
    {
        return $this->ceremony === $ceremony;
    }

    public function getUser(): ?SubjectUserInterface
    {
        return $this->user;
    }

    public function optionsJson(): string
    {
        return $this->optionsJson;
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
