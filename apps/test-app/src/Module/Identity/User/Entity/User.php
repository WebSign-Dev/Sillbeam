<?php

declare(strict_types=1);

namespace App\Module\Identity\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexum\User\Entity\AbstractUser;

#[ORM\Entity]
#[ORM\Table(name: 'nexum_user')]
final class User extends AbstractUser
{
}
