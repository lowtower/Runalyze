<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Analyse\AnalysisPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(AnalysisPresenter::class)
        ->autowire()
        ->tag('runalyze.modern_stat');
};
