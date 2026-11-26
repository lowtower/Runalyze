<?php

declare(strict_types=1);

use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorFormInput;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorModelHintRenderer;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorPredictionRows;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorResultTableRenderer;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorWindowRenderer;
use Runalyze\Plugins\Panels\Prognose\PrognosePanelPresenter;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendDataProvider;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendPlotRenderer;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendSeriesCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(PrognoseCalculatorFormInput::class);
    $services->set(PrognoseCalculatorModelFactory::class);
    $services->set(PrognoseCalculatorModelHintRenderer::class);
    $services->set(PrognoseCalculatorPersonalBestProvider::class);
    $services->set(PrognoseCalculatorPredictionRows::class);
    $services->set(PrognoseCalculatorResultTableRenderer::class);
    $services->set(PrognoseCalculatorWindowRenderer::class);
    $services->set(PrognoseTrendSeriesCalculator::class);
    $services->set(PrognoseTrendDataProvider::class);
    $services->set(PrognoseTrendPlotRenderer::class);

    $services->set(PrognosePanelPresenter::class)
        ->tag('runalyze.modern_panel');
};
