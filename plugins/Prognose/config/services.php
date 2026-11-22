<?php

declare(strict_types=1);

use Runalyze\Plugins\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Plugins\Prognose\PrognosePanelPresenter;
use Runalyze\Plugins\Prognose\PrognoseTrendDataProvider;
use Runalyze\Plugins\Prognose\PrognoseTrendPlotRenderer;
use Runalyze\Plugins\Prognose\PrognoseTrendSeriesCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(PrognoseCalculatorModelFactory::class);
    $services->set(PrognoseCalculatorPersonalBestProvider::class);
    $services->set(PrognoseTrendSeriesCalculator::class);
    $services->set(PrognoseTrendDataProvider::class);
    $services->set(PrognoseTrendPlotRenderer::class);

    $services->set(PrognosePanelPresenter::class)
        ->tag('runalyze.modern_panel');
};
