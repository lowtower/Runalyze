<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Wetter\TemperatureWindowPresenter;
use Runalyze\Plugins\Statistics\Wetter\WeatherStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(WeatherStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.stat');
    $services->set(TemperatureWindowPresenter::class)
        ->autowire();
};
