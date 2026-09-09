<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Entity\AbstractEntity;
use Nexum\Security\Contract\SubjectUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_passkey')]
#[ORM\Index(name: 'IDX_NEXUM_PASSKEY_USER', columns: ['user_id'])]
final class PasskeyCredential extends AbstractEntity
{
    #[ORM\Column(length: 64, unique: true)]
    private string $credentialIdHash;

    #[ORM\ManyToOne(targetEntity: SubjectUserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private SubjectUserInterface $user;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(type: Types::TEXT)]
    private string $recordJson;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        string $credentialIdHash,
        SubjectUserInterface $user,
        string $label,
        #[\SensitiveParameter] string $recordJson,
        \DateTimeImmutable $createdAt,
    ) {
        parent::__construct();
        $this->credentialIdHash = $credentialIdHash;
        $this->user = $user;
        $this->label = $label;
        $this->recordJson = $recordJson;
        $this->createdAt = $createdAt;
    }

    public function getUser(): SubjectUserInterface
    {
        return $this->user;
    }

    public function credentialIdHash(): string
    {
        return $this->credentialIdHash;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function recordJson(): string
    {
        return $this->recordJson;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function updateRecord(#[\SensitiveParameter] string $recordJson, \DateTimeImmutable $usedAt): void
    {
        $this->recordJson = $recordJson;
        $this->lastUsedAt = $usedAt;
    }
}
