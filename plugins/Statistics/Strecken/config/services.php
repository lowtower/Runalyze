<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Strecken\RouteNetworkPresenter;
use Runalyze\Plugins\Statistics\Strecken\RouteStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(RouteStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.modern_stat');
    $services->set(RouteNetworkPresenter::class)
        ->autowire();
};
