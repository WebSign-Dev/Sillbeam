<?php

declare(strict_types=1);

use Nexum\Management\Http\ModuleController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('nexum_management_modules', '/modules')
        ->controller([ModuleController::class, 'list'])
        ->methods(['GET'])
        ->defaults(['_nexum_api' => true]);
};
