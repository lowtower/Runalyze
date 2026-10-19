<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('db_test_empty_name', 'runalyze_test_empty');

    $parameters->set('database_prefix', 'runalyze_');
};
