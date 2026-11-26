<?php

declare(strict_types=1);

use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleExplanationWindowProvider;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleMarathonShapeSeriesCalculator;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspielePanelPresenter;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapeDailyAggregator;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapeDataProvider;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotDataProvider;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotDateRange;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotMaximaUpdater;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotOutputAdapter;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotRenderer;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotSeriesAssembler;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotTimestampMapper;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapeSeriesCalculator;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleTrainingLoadCalculator;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleTrimpDataProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(RechenspielePanelPresenter::class)
        ->tag('runalyze.modern_panel');

    $services->set(RechenspieleExplanationWindowProvider::class);
    $services->set(RechenspieleTrainingLoadCalculator::class);
    $services->set(RechenspieleShapeDailyAggregator::class);
    $services->set(RechenspieleShapeDataProvider::class);
    $services->set(RechenspieleShapePlotDataProvider::class);
    $services->set(RechenspieleShapeSeriesCalculator::class);
    $services->set(RechenspieleShapePlotRenderer::class);
    $services->set(RechenspieleShapePlotOutputAdapter::class);
    $services->set(RechenspieleShapePlotMaximaUpdater::class);
    $services->set(RechenspieleShapePlotDateRange::class);
    $services->set(RechenspieleShapePlotTimestampMapper::class);
    $services->set(RechenspieleShapePlotSeriesAssembler::class);
    $services->set(RechenspieleMarathonShapeSeriesCalculator::class);

    $services->set(RechenspieleTrimpDataProvider::class)
        ->autowire()
        ->autoconfigure();
};
