<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Nexum\Kernel\NexumKernelBundle::class => ['all' => true],
    Nexum\Http\NexumHttpBundle::class => ['all' => true],
    Nexum\Serializer\NexumSerializerBundle::class => ['all' => true],
    Nexum\Validator\NexumValidatorBundle::class => ['all' => true],
    Nexum\Doctrine\NexumDoctrineBundle::class => ['all' => true],
    Nexum\Api\NexumApiBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Nexum\Security\NexumSecurityBundle::class => ['all' => true],
    Nexum\User\NexumUserBundle::class => ['all' => true],
    Nexum\Security\Mfa\NexumSecurityMfaBundle::class => ['all' => true],
    Nexum\Security\Passkey\NexumSecurityPasskeyBundle::class => ['all' => true],
    Nexum\Filesystem\NexumFilesystemBundle::class => ['all' => true],
    Nexum\Mailer\NexumMailerBundle::class => ['all' => true],
    Nexum\Messenger\NexumMessengerBundle::class => ['all' => true],
    Nexum\QrCode\NexumQrCodeBundle::class => ['all' => true],
    Nexum\Management\NexumManagementBundle::class => ['all' => true],
];
