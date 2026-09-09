<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Entity\AbstractEntity;
use Nexum\Security\Contract\SubjectUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_mfa_totp')]
final class TotpEnrollment extends AbstractEntity
{
    #[ORM\OneToOne(targetEntity: SubjectUserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private SubjectUserInterface $user;

    #[ORM\Column(type: Types::TEXT)]
    private string $encryptedSecret;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $recoveryCodeHashes = [];

    #[ORM\Column(nullable: true)]
    private ?int $lastUsedTimeStep = null;

    public function __construct(SubjectUserInterface $user, #[\SensitiveParameter] string $encryptedSecret)
    {
        parent::__construct();
        $this->user = $user;
        $this->encryptedSecret = $encryptedSecret;
    }

    public function getUser(): SubjectUserInterface
    {
        return $this->user;
    }

    public function encryptedSecret(): string
    {
        return $this->encryptedSecret;
    }

    public function replacePendingSecret(#[\SensitiveParameter] string $encryptedSecret): void
    {
        if ($this->isConfirmed()) {
            throw new \LogicException('A confirmed MFA enrollment cannot be replaced.');
        }

        $this->encryptedSecret = $encryptedSecret;
    }

    public function isConfirmed(): bool
    {
        return null !== $this->confirmedAt;
    }

    /** @param list<string> $recoveryCodeHashes */
    public function confirm(\DateTimeImmutable $confirmedAt, array $recoveryCodeHashes): void
    {
        $this->confirmedAt ??= $confirmedAt;
        $this->recoveryCodeHashes = array_values($recoveryCodeHashes);
    }

    public function lastUsedTimeStep(): ?int
    {
        return $this->lastUsedTimeStep;
    }

    public function markTimeStepUsed(int $timeStep): void
    {
        $this->lastUsedTimeStep = $timeStep;
    }

    /** @return list<string> */
    public function recoveryCodeHashes(): array
    {
        return $this->recoveryCodeHashes;
    }

    public function consumeRecoveryCodeHash(string $usedHash): bool
    {
        foreach ($this->recoveryCodeHashes as $index => $hash) {
            if (!hash_equals($hash, $usedHash)) {
                continue;
            }

            array_splice($this->recoveryCodeHashes, $index, 1);

            return true;
        }

        return false;
    }
}
