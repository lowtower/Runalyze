<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Trainingspartner\TrainingPartnerStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(TrainingPartnerStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.stat');
};
