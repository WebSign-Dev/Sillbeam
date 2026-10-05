<?php

declare(strict_types=1);

namespace ProjectOnlyApp\Project\Portal\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class ProjectRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    public function __construct(string $name = 'example')
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
