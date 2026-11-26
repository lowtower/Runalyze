<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Wettkampf\RaceStatisticsPresenter;
use Runalyze\Plugins\Statistics\Wettkampf\RaceStatisticsSettings;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(RaceStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.stat');
    $services->set(RaceStatisticsSettings::class)
        ->autowire();
};
