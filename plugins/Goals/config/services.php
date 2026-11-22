<?php

declare(strict_types=1);

use Runalyze\Plugins\Goals\GoalsProgressCalculator;
use Runalyze\Plugins\Goals\GoalsTimeRangeProvider;
use Runalyze\Plugins\Goals\GoalsTrainingDataProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(GoalsProgressCalculator::class);
    $services->set(GoalsTimeRangeProvider::class);
    $services->set(GoalsTrainingDataProvider::class);
};
