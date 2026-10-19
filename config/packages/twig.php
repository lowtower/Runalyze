<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('twig', [
        'file_name_pattern' => '*.twig',
        'debug' => '%kernel.debug%',
        'strict_variables' => '%kernel.debug%',
        'globals' => [
            'RUNALYZE_VERSION' => '%RUNALYZE_VERSION%',
            'garmin_api_key' => '%app.garmin_api_key%',
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
