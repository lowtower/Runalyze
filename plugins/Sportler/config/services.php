<?php

declare(strict_types=1);

use Runalyze\Plugins\Sportler\SportlerPanelDataProvider;
use Runalyze\Plugins\Sportler\SportlerPanelPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(SportlerPanelDataProvider::class);
    $services->set(SportlerPanelPresenter::class)
        ->tag('runalyze.modern_panel');
};
