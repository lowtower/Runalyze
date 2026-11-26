<?php

declare(strict_types=1);

use Runalyze\Plugins\Panels\Goals\GoalsPanelPresenter;
use Runalyze\Plugins\Panels\Goals\GoalsProgressCalculator;
use Runalyze\Plugins\Panels\Goals\GoalsTimeRangeProvider;
use Runalyze\Plugins\Panels\Goals\GoalsTrainingDataProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(GoalsPanelPresenter::class)
        ->tag('runalyze.modern_panel');
    $services->set(GoalsProgressCalculator::class);
    $services->set(GoalsTimeRangeProvider::class);
    $services->set(GoalsTrainingDataProvider::class);
};
