<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('snc_redis', [
        'clients' => [
            'default' => [
                'type' => 'predis',
                'alias' => 'default',
                'dsn' => '%redis.default.dsn%',
            ],
        ],
    ]);
};
