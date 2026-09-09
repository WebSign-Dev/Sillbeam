<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Entity\AbstractTimestampedEntity;

#[ORM\Entity]
final class LegacyWidget extends AbstractTimestampedEntity
{
    #[ORM\Column(length: 120)]
    private string $name;

    public function __construct(string $name)
    {
        parent::__construct();
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
