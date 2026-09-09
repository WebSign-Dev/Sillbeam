<?php

declare(strict_types=1);

namespace Nexum\Entity\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexum\Entity\Contract\EntityInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\MappedSuperclass]
abstract class AbstractEntity implements EntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    protected Uuid $id;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
