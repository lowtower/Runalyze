<?php

declare(strict_types=1);

use Runalyze\Plugins\Sportler\SportlerPanelDataProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(SportlerPanelDataProvider::class);
};
