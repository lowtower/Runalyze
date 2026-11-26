<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('../src/CoreBundle/Controller/', 'attribute');
    $routingConfigurator->import('../plugins/{Panels,Statistics}/*/Controller/**/*.php', 'attribute');

    $routingConfigurator->import('../vendor/runalyze/glossary/Controller/', 'attribute')
        ->prefix('/glossary');

    $routingConfigurator->import('../src/Kernel.php', 'attribute');
};
