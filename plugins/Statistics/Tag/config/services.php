<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Tag\TagSelectionHandler;
use Runalyze\Plugins\Statistics\Tag\TagStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(TagSelectionHandler::class)
        ->autowire();
    $services->set(TagStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.stat');
};
