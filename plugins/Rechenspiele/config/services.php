<?php

declare(strict_types=1);

use Runalyze\Plugins\Rechenspiele\RechenspieleMarathonShapeSeriesCalculator;
use Runalyze\Plugins\Rechenspiele\RechenspielePanelPresenter;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDailyAggregator;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDataProvider;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotDataProvider;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotDateRange;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotOutputAdapter;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotRenderer;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotSeriesAssembler;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotTimestampMapper;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeSeriesCalculator;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrainingLoadCalculator;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrimpDataProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(RechenspielePanelPresenter::class)
        ->tag('runalyze.modern_panel');

    $services->set(RechenspieleTrainingLoadCalculator::class);
    $services->set(RechenspieleShapeDailyAggregator::class);
    $services->set(RechenspieleShapeDataProvider::class);
    $services->set(RechenspieleShapePlotDataProvider::class);
    $services->set(RechenspieleShapeSeriesCalculator::class);
    $services->set(RechenspieleShapePlotRenderer::class);
    $services->set(RechenspieleShapePlotOutputAdapter::class);
    $services->set(RechenspieleShapePlotDateRange::class);
    $services->set(RechenspieleShapePlotTimestampMapper::class);
    $services->set(RechenspieleShapePlotSeriesAssembler::class);
    $services->set(RechenspieleMarathonShapeSeriesCalculator::class);

    $services->set(RechenspieleTrimpDataProvider::class)
        ->autowire()
        ->autoconfigure();
};
