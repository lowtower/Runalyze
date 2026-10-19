<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine_migrations', [
        'enable_service_migrations' => true,
        'migrations_paths' => [
            'Runalyze\Migrations' => '%kernel.project_dir%/migrations',
        ],
        'storage' => [
            'table_storage' => [
                'table_name' => '%app.database_prefix%migration_versions',
                'version_column_name' => 'version',
            ],
        ],
        'enable_profiler' => false,
    ]);
};
