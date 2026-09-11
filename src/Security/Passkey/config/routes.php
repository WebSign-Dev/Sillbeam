<?php

declare(strict_types=1);

use Nexum\Security\Passkey\Controller\PasskeyController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nexum_security_passkey_registration_options', '/register/options')
        ->controller([PasskeyController::class, 'registrationOptions'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_passkey_register', '/register')
        ->controller([PasskeyController::class, 'register'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_passkey_authentication_options', '/authenticate/options')
        ->controller([PasskeyController::class, 'authenticationOptions'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_passkey_authenticate', '/authenticate')
        ->controller([PasskeyController::class, 'authenticate'])->methods(['POST'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_passkey_list', '')
        ->controller([PasskeyController::class, 'list'])->methods(['GET'])->defaults(['_nexum_api' => true]);
    $routes->add('nexum_security_passkey_remove', '/{id}')
        ->controller([PasskeyController::class, 'remove'])->methods(['DELETE'])->defaults(['_nexum_api' => true]);
};
