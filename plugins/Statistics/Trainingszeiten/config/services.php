<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Trainingszeiten\TrainingTimeDistribution;
use Runalyze\Plugins\Statistics\Trainingszeiten\TrainingTimeStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(TrainingTimeDistribution::class);
    $services->set(TrainingTimeStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.modern_stat');
};
