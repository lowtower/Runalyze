<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\MonthlyStats\MonthlyStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(MonthlyStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.stat');
};
