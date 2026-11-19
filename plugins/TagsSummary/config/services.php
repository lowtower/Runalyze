<?php

declare(strict_types=1);

use Runalyze\Plugins\TagsSummary\TagsSummaryPanelPresenter;
use Runalyze\Plugins\TagsSummary\TagsSummaryPanelSelectionHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(TagsSummaryPanelSelectionHandler::class);

    $services->set(TagsSummaryPanelPresenter::class)
        ->tag('runalyze.modern_panel');
};
