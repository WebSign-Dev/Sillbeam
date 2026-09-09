<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

use Symfony\Component\Security\Core\User\UserInterface;

interface SecondFactorGateInterface
{
    public function begin(UserInterface $user): ?SecondFactorChallenge;
}
