<?php

declare(strict_types=1);

use Runalyze\Plugins\Statistics\Rekorde\RecordStatisticsPresenter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->services()
        ->set(RecordStatisticsPresenter::class)
        ->autowire()
        ->tag('runalyze.stat');
};
