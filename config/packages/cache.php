<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'cache' => [
            'pools' => [
                'app.cache.activity_uploads' => [
                    'adapter' => 'cache.app',
                    'public' => true,
                    'default_lifetime' => 86400,
                ],
                'doctrine.system_cache_pool' => [
                    'adapter' => 'cache.system',
                ],
                'doctrine.result_cache_pool' => [
                    'adapter' => 'cache.app',
                ],
            ],
        ],
    ]);
};
