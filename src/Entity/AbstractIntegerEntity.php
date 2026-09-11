<?php

declare(strict_types=1);

namespace Nexum\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Contract\EntityInterface;

#[ORM\MappedSuperclass]
abstract class AbstractIntegerEntity implements EntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
