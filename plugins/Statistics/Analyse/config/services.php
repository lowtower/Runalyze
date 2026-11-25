<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Analyse\AnalysisStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(AnalysisStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.modern_stat');
};
