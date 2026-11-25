<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Statistiken\DatasetSummaryRepository;
use Runalyze\Plugins\Statistics\Statistiken\SummaryStatisticsPresenter;
use Runalyze\Plugins\Statistics\Statistiken\SummaryTableProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(DatasetSummaryRepository::class)->autowire();
    $services->set(SummaryTableProvider::class)->autowire();
    $services->set(SummaryStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.modern_stat');
};
