<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('app.database_host', '%env(string:DB_TEST_HOST)%');

    $parameters->set('app.database_port', '%env(int:DB_TEST_PORT)%');

    $parameters->set('app.database_name', '%env(string:DB_UNITTEST_NAME)%');

    $parameters->set('app.database_user', '%env(string:DB_TEST_USER)%');

    $parameters->set('app.database_password', '%env(string:DB_TEST_PASSWORD)%');

    $parameters->set('app.openweathermap_api_key', '');

    $parameters->set('app.meteostatnet_api_key', '');

    $parameters->set('app.darksky_api_key', '');

    $parameters->set('test_sqlite_path', '%kernel.cache_dir%/test.db');

    $parameters->set('database_prefix', 'runalyze_test_');

    $parameters->set('database_prefix.functional', 'runalyze_test_');

    $parameters->set('database_prefix.unittest', 'runalyze_');
};
