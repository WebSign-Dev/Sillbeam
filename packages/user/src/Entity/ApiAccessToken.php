<?php

declare(strict_types=1);

namespace Nexum\User\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Entity\AbstractEntity;
use Nexum\Security\Contract\SubjectUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_access_token')]
final class ApiAccessToken extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: SubjectUserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private SubjectUserInterface $user;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $issuedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(
        SubjectUserInterface $user,
        #[\SensitiveParameter]
        string $tokenHash,
        ?string $label,
        \DateTimeImmutable $issuedAt,
        \DateTimeImmutable $expiresAt,
    ) {
        parent::__construct();
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->label = $label;
        $this->issuedAt = $issuedAt;
        $this->expiresAt = $expiresAt;
    }

    public function getUser(): SubjectUserInterface
    {
        return $this->user;
    }

    public function matchesHash(#[\SensitiveParameter] string $tokenHash): bool
    {
        return hash_equals($this->tokenHash, $tokenHash);
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function isActiveAt(\DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && $this->expiresAt > $now;
    }

    public function revoke(\DateTimeImmutable $revokedAt): void
    {
        $this->revokedAt ??= $revokedAt;
    }
}
