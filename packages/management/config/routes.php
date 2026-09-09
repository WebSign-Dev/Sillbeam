<?php

declare(strict_types=1);

use Nexum\Management\Http\ModuleController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nexum_management_modules', '/modules')
        ->controller([ModuleController::class, 'list'])
        ->methods(['GET'])
        ->defaults(['_nexum_api' => true]);
    $routes->add('nexum_management_module_update', '/modules/{id}')
        ->controller([ModuleController::class, 'update'])
        ->methods(['PATCH', 'DELETE'])
        ->requirements(['id' => '[a-z][a-z0-9_.-]{0,63}'])
        ->defaults(['_nexum_api' => true]);
};
