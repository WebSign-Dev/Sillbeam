<?php

declare(strict_types=1);

namespace Nexum\Security\Contract;

use Nexum\Core\Contract\SubjectInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface SubjectUserInterface extends SubjectInterface, UserInterface
{
}
