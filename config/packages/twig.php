<?php

declare(strict_types=1);

use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container): void {
    $projectDir = $container->getParameter('kernel.project_dir');
    assert(is_string($projectDir));

    // Track the template directories themselves, including newly added directories.
    $templateDirectories = new GlobResource($projectDir . '/plugins', '/{Panels,Statistics}/*/templates', false, forExclusion: true);
    $container->addResource($templateDirectories);
    $pathsByNamespace = [];

    foreach ($templateDirectories as $path => $directory) {
        if (! $directory->isDir()) {
            continue;
        }

        $namespace = basename(dirname($path));
        if (isset($pathsByNamespace[$namespace])) {
            throw new LogicException(sprintf('The plugin Twig namespace "%s" is used by both "%s" and "%s".', $namespace, $pathsByNamespace[$namespace], $path));
        }

        $pathsByNamespace[$namespace] = $path;
    }

    $containerConfigurator->extension('twig', [
        'file_name_pattern' => '*.twig',
        'paths' => array_flip($pathsByNamespace),
        'debug' => '%kernel.debug%',
        'strict_variables' => '%kernel.debug%',
        'globals' => [
            'RUNALYZE_VERSION' => '%RUNALYZE_VERSION%',
            'RUNALYZE_DATE' => '%RUNALYZE_DATE%',
            'feedback_mail' => '%app.feedback_mail%',
            'weather_proxy' => '%app.weather_proxy%',
            'openweathermap_api_key' => '%app.openweathermap_api_key%',
            'meteostatnet_api_key' => '%app.meteostatnet_api_key%',
            'darksky_api_key' => '%app.darksky_api_key%',
            'nokia_here_appid' => '%app.nokia_here_appid%',
            'nokia_here_token' => '%app.nokia_here_token%',
            'thunderforest_api_key' => '%app.thunderforest_api_key%',
            'mapbox_api_key' => '%app.mapbox_api_key%',
            'locales' => '%locales%',
            'osm_overpass_url' => '%app.osm_overpass_url%',
            'osm_overpass_proxy' => '%app.osm_overpass_proxy%',
            'sharedView' => true,
            'input_unit' => '',
        ],
        'form_themes' => [
            'form/fields.html.twig',
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('twig', [
            'strict_variables' => true,
        ]);
    }
};
