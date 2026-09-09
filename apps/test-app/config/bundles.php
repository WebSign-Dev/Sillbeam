<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Nexum\Kernel\NexumKernelBundle::class => ['all' => true],
    Nexum\Doctrine\NexumDoctrineBundle::class => ['all' => true],
    Nexum\Api\NexumApiBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Nexum\Security\NexumSecurityBundle::class => ['all' => true],
    Nexum\User\NexumUserBundle::class => ['all' => true],
    Nexum\Security\Mfa\NexumSecurityMfaBundle::class => ['all' => true],
    Nexum\Security\Passkey\NexumSecurityPasskeyBundle::class => ['all' => true],
];
