<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'http_method_override' => false,
        'handle_all_throwables' => true,
        'serializer' => [
            'enable_attributes' => true,
        ],
        'session' => [
            'storage_factory_id' => 'session.storage.factory.native',
            'handler_id' => '%session.handler_id%',
            'save_path' => '%app.data_directory%/sessions',
            'cookie_secure' => 'auto',
            'cookie_samesite' => 'lax',
        ],
        'form' => null,
        'assets' => null,
        'translator' => [
            'fallbacks' => [
                '%locale%',
            ],
            'paths' => [
                '%kernel.project_dir%/vendor/runalyze/translations/xlf',
                '%kernel.project_dir%/vendor/runalyze/translations/gettext',
            ],
        ],
        'php_errors' => [
            'log' => true,
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
                'name' => 'MOCKSESSID',
            ],
        ]);
    }

    if ($containerConfigurator->env() === 'test_empty') {
        $containerConfigurator->extension('framework', [
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
                'name' => 'MOCKSESSID',
            ],
        ]);
    }
};
