<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('liip_test_fixtures', [
            'keep_database_and_schema' => true,
            'cache_metadata' => false,
            'cache_db' => [
                'sqlite' => null,
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'test_empty') {
        $containerConfigurator->extension('liip_test_fixtures', [
            'keep_database_and_schema' => true,
            'cache_metadata' => false,
            'cache_db' => [
                'sqlite' => null,
            ],
        ]);
    }
};
