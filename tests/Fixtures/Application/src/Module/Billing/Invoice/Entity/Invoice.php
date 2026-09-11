<?php

declare(strict_types=1);

namespace App\Module\Billing\Invoice\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\AbstractSoftDeletableEntity;
use Nexum\Security\Contract\SubjectUserInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
final class Invoice extends AbstractSoftDeletableEntity
{
    #[ORM\ManyToOne(targetEntity: SubjectUserInterface::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: true, onDelete: 'CASCADE')]
    private ?SubjectUserInterface $owner = null;

    #[ORM\Column(length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $number;

    public function __construct(string $number)
    {
        parent::__construct();
        $this->number = $number;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function assignOwner(SubjectUserInterface $owner): void
    {
        $this->owner = $owner;
    }

    #[Ignore]
    public function getOwner(): ?SubjectUserInterface
    {
        return $this->owner;
    }
}
