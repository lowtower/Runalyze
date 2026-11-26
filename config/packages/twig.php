<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('twig', [
        'file_name_pattern' => '*.twig',
        'paths' => [
            '%kernel.project_dir%/plugins/Panels/Equipment/templates' => 'Equipment',
            '%kernel.project_dir%/plugins/Panels/Goals/templates' => 'Goals',
            '%kernel.project_dir%/plugins/Panels/Prognose/templates' => 'Prognose',
            '%kernel.project_dir%/plugins/Panels/Rechenspiele/templates' => 'Rechenspiele',
            '%kernel.project_dir%/plugins/Panels/Sportler/templates' => 'Sportler',
            '%kernel.project_dir%/plugins/Panels/Sports/templates' => 'Sports',
            '%kernel.project_dir%/plugins/Panels/TagsSummary/templates' => 'TagsSummary',
            '%kernel.project_dir%/plugins/Statistics/Analyse/templates' => 'Analyse',
            '%kernel.project_dir%/plugins/Statistics/Hoehenmeter/templates' => 'Hoehenmeter',
            '%kernel.project_dir%/plugins/Statistics/MonthlyStats/templates' => 'MonthlyStats',
            '%kernel.project_dir%/plugins/Statistics/Rekorde/templates' => 'Rekorde',
            '%kernel.project_dir%/plugins/Statistics/Statistiken/templates' => 'Statistiken',
            '%kernel.project_dir%/plugins/Statistics/Strecken/templates' => 'Strecken',
            '%kernel.project_dir%/plugins/Statistics/Tag/templates' => 'Tag',
            '%kernel.project_dir%/plugins/Statistics/Trainingspartner/templates' => 'Trainingspartner',
            '%kernel.project_dir%/plugins/Statistics/Trainingszeiten/templates' => 'Trainingszeiten',
            '%kernel.project_dir%/plugins/Statistics/Wetter/templates' => 'Wetter',
            '%kernel.project_dir%/plugins/Statistics/Wettkampf/templates' => 'Wettkampf',
        ],
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
