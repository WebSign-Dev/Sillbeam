<?php

declare(strict_types=1);

use Nexum\Security\Mfa\Controller\MfaController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nexum_security_mfa_status', '/status')
        ->controller([MfaController::class, 'status'])->methods(['GET'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_mfa_enroll', '/totp')
        ->controller([MfaController::class, 'beginEnrollment'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_mfa_confirm', '/totp/confirm')
        ->controller([MfaController::class, 'confirmEnrollment'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_mfa_verify', '/verify')
        ->controller([MfaController::class, 'verify'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_mfa_disable', '/totp')
        ->controller([MfaController::class, 'disable'])->methods(['DELETE'])->defaults(['_nexum_api' => true]);
};
