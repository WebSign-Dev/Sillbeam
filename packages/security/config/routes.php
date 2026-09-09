<?php

declare(strict_types=1);

use Nexum\Security\Controller\TokenController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nexum_security_token_issue', '/token')
        ->controller([TokenController::class, 'issue'])
        ->methods(['POST'])
        ->defaults(['_nexum_api' => true]);

    $routes->add('nexum_security_token_revoke', '/token')
        ->controller([TokenController::class, 'revoke'])
        ->methods(['DELETE'])
        ->defaults(['_nexum_api' => true]);

    $routes->add('nexum_security_current_user', '/me')
        ->controller([TokenController::class, 'current'])
        ->methods(['GET'])
        ->defaults(['_nexum_api' => true]);
};
