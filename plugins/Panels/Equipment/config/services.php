<?php

declare(strict_types=1);

use Runalyze\Plugins\Panels\Equipment\EquipmentPanelPresenter;
use Runalyze\Plugins\Panels\Equipment\EquipmentPanelSelectionHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(EquipmentPanelSelectionHandler::class);

    $services->set(EquipmentPanelPresenter::class)
        ->tag('runalyze.panel');
};
