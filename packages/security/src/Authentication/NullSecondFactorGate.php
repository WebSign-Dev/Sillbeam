<?php

declare(strict_types=1);

namespace Nexum\Security\Authentication;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class NullSecondFactorGate implements SecondFactorGateInterface
{
    public function begin(UserInterface $user): ?SecondFactorChallenge
    {
        return null;
    }
}
