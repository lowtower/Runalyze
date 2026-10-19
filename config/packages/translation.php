<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'default_locale' => '%app.locale%',
        'translator' => [
            'default_path' => '%kernel.project_dir%/translations',
            'fallbacks' => '%app.locale%',
            'paths' => [
                '%kernel.project_dir%/vendor/runalyze/translations/xlf',
            ],
            'providers' => null,
        ],
    ]);
};
