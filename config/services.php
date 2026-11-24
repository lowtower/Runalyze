<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client;
use Runalyze\Bundle\CoreBundle\ArgumentResolver\AccountValueResolver;
use Runalyze\Bundle\CoreBundle\Command\ActivityBulkRouteNameEvaluationCommand;
use Runalyze\Bundle\CoreBundle\Command\CleanupBackupCommand;
use Runalyze\Bundle\CoreBundle\Command\InstallDatabaseCommand;
use Runalyze\Bundle\CoreBundle\Command\InstallFilesystemCommand;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FinderFactory;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FinderFactoryInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Availability;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GenerateJsonData;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GeneratePoster;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\PosterGenerationStateResolver;
use Runalyze\Bundle\CoreBundle\EntityListener\EquipmentUpdateSubscriber;
use Runalyze\Bundle\CoreBundle\EventListener\AccountLastActionListener;
use Runalyze\Bundle\CoreBundle\EventListener\ConsoleExceptionListener;
use Runalyze\Bundle\CoreBundle\EventListener\LocaleListener;
use Runalyze\Bundle\CoreBundle\EventListener\MaintenanceListener;
use Runalyze\Bundle\CoreBundle\EventListener\RecalculationTasksPerformerListener;
use Runalyze\Bundle\CoreBundle\EventListener\TablePrefixSubscriber;
use Runalyze\Bundle\CoreBundle\EventListener\UserLocaleListener;
use Runalyze\Bundle\CoreBundle\EventListener\UserTimezoneListener;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Form\EquipmentCategoryType;
use Runalyze\Bundle\CoreBundle\Form\EquipmentType as EquipmentFormType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportTypeType;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaType;
use Runalyze\Bundle\CoreBundle\Form\Tools\PosterType;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisType;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityEquipmentType;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityLapIntensityChoiceType;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityTypeChoiceType;
use Runalyze\Bundle\CoreBundle\Form\Type\SportChoiceType;
use Runalyze\Bundle\CoreBundle\MessageHandler\BackupGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\MessageHandler\PosterGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Repository\WeathercacheRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountChecker;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Activity\AgeGradeLookup;
use Runalyze\Bundle\CoreBundle\Services\Activity\DataSeriesRemover;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Dashboard\DashboardStatisticsProvider;
use Runalyze\Bundle\CoreBundle\Services\DisposableDomainProvider;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityCache;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\GeoTiffReader;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Runalyze\Bundle\CoreBundle\Services\PrivacyGuard;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Services\SessionRequestProcessor;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
use Runalyze\Bundle\CoreBundle\Services\SharedAthleteStatisticsProvider;
use Runalyze\Bundle\CoreBundle\Services\UnitSystemFactory;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\ORM\LoadAccountData;
use Runalyze\Bundle\CoreBundle\Twig\AutomaticReloadFlagExtension;
use Runalyze\Bundle\CoreBundle\Twig\ConfigurationExtension;
use Runalyze\Bundle\CoreBundle\Twig\HtmlExtension;
use Runalyze\Bundle\CoreBundle\Twig\UtilityExtension;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit;
use Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit;
use Runalyze\Metrics\Weight\Unit\AbstractWeightUnit;
use Runalyze\Parser\Activity\Converter\FitConverter;
use Runalyze\Parser\Activity\Converter\TtbinConverter;
use Runalyze\Service\ElevationCorrection\Strategy\Geonames;
use Runalyze\Service\ElevationCorrection\Strategy\GeoTiff;
use Runalyze\Service\ElevationCorrection\Strategy\GoogleMaps;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalFactory;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalOsm;
use Runalyze\Service\WeatherForecast\Strategy\DarkSky;
use Runalyze\Service\WeatherForecast\Strategy\DatabaseCache;
use Runalyze\Service\WeatherForecast\Strategy\MeteostatNet;
use Runalyze\Service\WeatherForecast\Strategy\OpenWeatherMap;
use Runalyze\View\Activity\Feed;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $localesFile = __DIR__ . '/locales.php';
    $locales = file_exists($localesFile) ? require $localesFile : [];

    $parameters = $containerConfigurator->parameters();

    $parameters->set('locales', $locales);

    $parameters->set('RUNALYZE_VERSION', '%env(string:RUNALYZE_VERSION)%');

    $parameters->set('RUNALYZE_DATE', '%env(string:RUNALYZE_DATE)%');

    $parameters->set('locale', '%env(string:LOCALE)%');

    $parameters->set('app.locale', '%env(string:LOCALE)%');

    $parameters->set('app.secret', '%env(string:APP_SECRET)%');

    $parameters->set('app.update_disabled', '%env(string:UPDATE_DISABLED)%');

    $parameters->set('app.user_can_register', '%env(bool:USER_CAN_REGISTER)%');

    $parameters->set('app.user_disable_account_activation', '%env(bool:USER_DISABLE_ACCOUNT_ACTIVATION)%');

    $parameters->set('app.maintenance', '%env(bool:MAINTENANCE)%');

    $parameters->set('app.backup_storage_period', '%env(int:BACKUP_STORAGE_PERIOD)%');

    $parameters->set('app.poster_storage_period', '%env(int:POSTER_STORAGE_PERIOD)%');

    $parameters->set('app.feedback_mail', '%env(string:FEEDBACK_MAIL)%');

    $parameters->set('app.database_host', '%env(string:DATABASE_HOST)%');

    $parameters->set('app.database_prefix', '%env(string:DATABASE_PREFIX)%');

    $parameters->set('app.database_port', '%env(int:DATABASE_PORT)%');

    $parameters->set('app.database_name', '%env(string:DATABASE_NAME)%');

    $parameters->set('app.database_user', '%env(string:DATABASE_USER)%');

    $parameters->set('app.database_password', '%env(string:DATABASE_PASSWORD)%');

    $parameters->set('app.mail_sender', '%env(MAIL_SENDER)%');

    $parameters->set('app.mail_name', '%env(MAIL_NAME)%');

    $parameters->set('app.mail_localdomain', '%env(MAIL_LOCALDOMAIN)%');

    $parameters->set('app.smtp_host', '%env(string:SMTP_HOST)%');

    $parameters->set('app.smtp_port', '%env(int:SMTP_PORT)%');

    $parameters->set('app.smtp_security', '%env(string:SMTP_SECURITY)%');

    $parameters->set('app.smtp_username', '%env(string:SMTP_USERNAME)%');

    $parameters->set('app.smtp_password', '%env(string:SMTP_PASSWORD)%');

    $parameters->set('app.weather_max_age_for_current_conditions', '%env(int:WEATHER_MAX_AGE_FOR_CURRENT_CONDITIONS)%');

    $parameters->set('app.weather_proxy', '%env(string:WEATHER_PROXY)%');

    $parameters->set('app.openweathermap_api_key', '%env(string:OPENWEATHERMAP_API_KEY)%');

    $parameters->set('app.meteostatnet_api_key', '%env(string:METEOSTAT_API_KEY)%');

    $parameters->set('app.darksky_api_key', '%env(string:DARKSKY_API_KEY)%');

    $parameters->set('app.nokia_here_appid', '%env(string:NOKIA_HERE_APPID)%');

    $parameters->set('app.nokia_here_token', '%env(string:NOKIA_HERE_TOKEN)%');

    $parameters->set('app.thunderforest_api_key', '%env(string:THUNDERFOREST_API_KEY)%');

    $parameters->set('app.mapbox_api_key', '%env(string:MAPBOX_API_KEY)%');

    $parameters->set('app.geonames_username', '%env(string:GEONAMES_USERNAME)%');

    $parameters->set('app.osm_overpass_url', '%env(string:OSM_OVERPASS_URL)%');

    $parameters->set('app.osm_overpass_proxy', '%env(string:OSM_OVERPASS_PROXY)%');

    $parameters->set('app.perl_path', '%env(string:PERL_PATH)%');

    $parameters->set('app.python3_path', '%env(string:PYTHON3_PATH)%');

    $parameters->set('app.rsvg_path', '%env(string:RSVG_PATH)%');

    $parameters->set('app.inkscape_path', '%env(string:INKSCAPE_PATH)%');

    $parameters->set('app.ttbin_path', '%env(string:TTBIN_PATH)%');

    $parameters->set('app.sqlite_mod_spatialite', '%env(string:SQLITE_MOD_SPATIALITE)%');

    $parameters->set('app.data_directory', '%kernel.project_dir%/data');

    $parameters->set('redis.default.dsn', '%env(string:REDIS_URL)%');

    $parameters->set('router.request_context.host', 'localhost');

    $parameters->set('router.request_context.scheme', 'http');

    $parameters->set('router.request_context.base_url', '');

    $parameters->set('session.handler_id', 'session.handler.native_file');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public();

    $services->set(PluginDiscovery::class)
        ->arg('$pluginDirectory', '%kernel.project_dir%/plugin');

    $services->set(PluginInstallationManager::class);

    $services->set(PluginSettingsProvider::class);

    $services->set(ModernPanelRegistry::class)
        ->arg('$panels', tagged_iterator('runalyze.modern_panel'));

    $services->set(ModernStatisticsRegistry::class)
        ->arg('$statistics', tagged_iterator('runalyze.modern_stat'));

    $services->set(StatisticsFilterFactory::class);

    $containerConfigurator->import(__DIR__ . '/../plugins/Statistics/MonthlyStats/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/Statistics/Trainingspartner/config/services.php');

    $containerConfigurator->import(__DIR__ . '/../plugins/Equipment/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/Goals/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/Sportler/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/Sports/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/Prognose/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/TagsSummary/config/services.php');
    $containerConfigurator->import(__DIR__ . '/../plugins/Rechenspiele/config/services.php');

    $services->set(SharedAthleteStatisticsProvider::class);

    $services->set(DoctrinePluginConfigurationStorage::class);
    $services->alias(PluginConfigurationStorageInterface::class, DoctrinePluginConfigurationStorage::class);

    $services->set(LegacyPluginOutputRenderer::class);

    $services->set(LegacyContentResponseFactory::class);

    $services->set(LegacyPluginInstantiator::class)
        ->arg('$configurationStorage', service(PluginConfigurationStorageInterface::class));
    $services->set(DashboardStatisticsProvider::class);

    $services->load('Runalyze\Bundle\CoreBundle\Controller\\', __DIR__ . '/../src/CoreBundle/Controller')
        ->tag('controller.service_arguments');

    $services->load('Runalyze\Bundle\GlossaryBundle\Controller\\', __DIR__ . '/../vendor/runalyze/glossary/Controller')
        ->tag('controller.service_arguments');

    $services->load('Runalyze\Bundle\CoreBundle\Command\\', __DIR__ . '/../src/CoreBundle/Command');

    $services->load('Runalyze\Bundle\CoreBundle\Repository\\', __DIR__ . '/../src/CoreBundle/Repository')
        ->exclude(__DIR__ . '/../src/CoreBundle/Repository/{DatasetRepository.php,HrvRepository.php,PluginConfRepository.php,PluginRepository.php,RouteRepository.php,SwimdataRepository.php,TrackdataRepository.php}');

    $services->set(CleanupBackupCommand::class);

    $services->set(InstallDatabaseCommand::class);

    $services->set(InstallFilesystemCommand::class);

    $services->set(ActivityBulkRouteNameEvaluationCommand::class)
        ->arg('$osmOverpassUrl', '%app.osm_overpass_url%');

    $services->set(FinderFactory::class);

    $services->alias(FinderFactoryInterface::class, FinderFactory::class);

    $services->load('Runalyze\Migrations\\', __DIR__ . '/../migrations/*')
        ->bind('$DatabasePrefix', '%app.database_prefix%');

    $services->alias(EntityManager::class, 'doctrine.orm.default_entity_manager');
    $services->alias(ObjectManager::class, 'doctrine.orm.default_entity_manager');

    $services->set(AccountValueResolver::class)
        ->autoconfigure(false)
        ->tag('controller.argument_value_resolver', [
            'priority' => 150,
        ]);

    $services->load('Runalyze\\Bundle\\CoreBundle\\EventListener\\', __DIR__ . '/../src/CoreBundle/EventListener/{AccountLastActionListener,ConsoleExceptionListener,LocaleListener,MaintenanceListener,UserLocaleListener,UserTimezoneListener}.php');

    $services->alias('app.listener.console_exception', ConsoleExceptionListener::class);

    $services->alias('app.locale_listener', LocaleListener::class);

    $services->alias('app.listener.user_timezone', UserTimezoneListener::class);

    $services->alias('app.user_locale_listener', UserLocaleListener::class);

    $services->alias('app.account_last_action_listener', AccountLastActionListener::class);

    $services->set(RecalculationTasksPerformerListener::class)
        ->arg('$Manager', service('app.recalculation_manager'));

    $services->alias('app.listener.recalculation_tasks_performer_listener', RecalculationTasksPerformerListener::class);

    $services->set(Sha256SaltedEncoder::class);

    $services->set(UnsubscribeHashGenerator::class)
        ->arg('$appSecret', '%app.secret%');

    $services->set(TablePrefixSubscriber::class)
        ->arg('$prefix', '%app.database_prefix%');

    $services->alias('app.listener.maintenance', MaintenanceListener::class);

    $services->set(AccountMailer::class);

    $services->set(LegacyCache::class)
        ->arg('$path', '%app.data_directory%');

    $services->set(LoadAccountData::class)
        ->arg('$passwordHasher', service('security.user_password_hasher'))
        ->tag('doctrine.fixture.orm');

    $services->set(Registration::class);

    $services->set(AccountChecker::class);






















    $services->load('Runalyze\\Bundle\\CoreBundle\\EntityListener\\', __DIR__ . '/../src/CoreBundle/EntityListener/{ActivityListener,RaceResultListener,RouteListener,UserListener}.php');

    $services->set(EquipmentUpdateSubscriber::class)
        ->tag('doctrine.event_listener', [
            'event' => 'onFlush',
            'connection' => 'default',
            'lazy' => true,
        ]);




    $services->load('Runalyze\\Bundle\\CoreBundle\\Form\\', __DIR__ . '/../src/CoreBundle/Form/{BodyValuesType,MultiImporterType,RaceResultType}.php');

    $services->set(SportTypeType::class);

    $services->load('Runalyze\\Bundle\\CoreBundle\\Form\\Type\\', __DIR__ . '/../src/CoreBundle/Form/Type/{DistanceType,ElevationType,EnergyType,EnergyKcalType,HrZoneBoundType,PowerType,TemperatureType,WeightType,WindSpeedType}.php');

    $services->set(ActivityType::class);


    $services->set(ActivityEquipmentType::class);

    $services->set(AnovaType::class);

    $services->set(TrendAnalysisType::class);

    $services->set(PosterType::class);

    $services->set(EquipmentFormType::class);

    $services->set(EquipmentCategoryType::class);

    $services->set(SportType::class);




    $services->set(SportChoiceType::class);

    $services->set(ActivityTypeChoiceType::class);

    $services->set(ActivityLapIntensityChoiceType::class);


    $services->set(AutomaticReloadFlagSetter::class);

    $services->set(PrivacyGuard::class);

    $services->set(ActivityContextFactory::class);

    $services->alias('app.activity_context.factory', ActivityContextFactory::class);

    $services->set(ConfigurationManager::class);

    $services->set(ConfigurationUpdater::class)
        ->lazy(true);

    $services->set('app.recalculation_manager', RecalculationManager::class)
        ->arg('$ConfigurationManager', service(ConfigurationManager::class))
        ->arg('$ConfigurationUpdater', service(ConfigurationUpdater::class))
        ->arg('$TrainingRepository', service(TrainingRepository::class))
        ->arg('$RaceResultRepository', service(RaceresultRepository::class))
        ->lazy(true);

    $services->alias(RecalculationManager::class, 'app.recalculation_manager');

    $services->set(SportSelectionFactory::class);

    $services->set(UnitSystemFactory::class);

    $services->set('app.unit_system.distance', AbstractDistanceUnit::class)
        ->factory([
            service(UnitSystemFactory::class),
            'getDistanceUnit',
        ])
        ->args([
            service(ConfigurationManager::class),
        ]);

    $services->set('app.unit_system.elevation', AbstractDistanceUnit::class)
        ->factory([
            service(UnitSystemFactory::class),
            'getElevationUnit',
        ])
        ->args([
            service(ConfigurationManager::class),
        ]);

    $services->alias(AbstractDistanceUnit::class . ' $distanceUnit', 'app.unit_system.distance');

    $services->alias(AbstractDistanceUnit::class . ' $elevationUnit', 'app.unit_system.elevation');

    $services->set('app.unit_system.energy', AbstractEnergyUnit::class)
        ->factory([
            service(UnitSystemFactory::class),
            'getEnergyUnit',
        ])
        ->args([
            service(ConfigurationManager::class),
        ]);

    $services->alias(AbstractEnergyUnit::class . ' $energyUnit', 'app.unit_system.energy');

    $services->set('app.unit_system.temperature', AbstractTemperatureUnit::class)
        ->factory([
            service(UnitSystemFactory::class),
            'getTemperatureUnit',
        ])
        ->args([
            service(ConfigurationManager::class),
        ]);

    $services->alias(AbstractTemperatureUnit::class . ' $temperatureUnit', 'app.unit_system.temperature');

    $services->set('app.unit_system.wind_speed', AbstractPaceInDecimalFormatUnit::class)
        ->factory([
            service(UnitSystemFactory::class),
            'getWindSpeedUnit',
        ])
        ->args([
            service(ConfigurationManager::class),
        ]);

    $services->alias(AbstractPaceInDecimalFormatUnit::class . ' $velocityUnit', 'app.unit_system.wind_speed');

    $services->set('app.unit_system.weight', AbstractWeightUnit::class)
        ->factory([
            service(UnitSystemFactory::class),
            'getWeightUnit',
        ])
        ->args([
            service(ConfigurationManager::class),
        ]);

    $services->alias(AbstractWeightUnit::class . ' $weightUnit', 'app.unit_system.weight');

    $containerConfigurator->extension('twig', [
        'globals' => [
            'locales' => '%locales%',
        ],
    ]);

    $services->load('Runalyze\Bundle\CoreBundle\Twig\\', __DIR__ . '/../src/CoreBundle/Twig/*Extension.php')
        ->tag('twig.attribute_extension');

    $services->alias('app.twig_configuration_extension', ConfigurationExtension::class);
    $services->alias('app.twig_html_extension', HtmlExtension::class);
    $services->alias('app.twig_utility_extension', UtilityExtension::class);
    $services->alias('app.twig_value_extension', ValueExtension::class);
    $services->alias('app.twig_automatic_reload_flag_extension', AutomaticReloadFlagExtension::class);

    $services->set(GenerateJsonData::class)
        ->arg('$TrainingRepository', service(TrainingRepository::class))
        ->arg('$RaceresultRepository', service(RaceresultRepository::class))
        ->arg('$projectDir', '%kernel.project_dir%');

    $services->set(GeneratePoster::class)
        ->arg('$projectDir', '%kernel.project_dir%')
        ->arg('$Python3Path', '%app.python3_path%')
        ->arg('$TrainingRepository', service(TrainingRepository::class));

    $services->set(Availability::class)
        ->arg('$RsvgPath', '%app.rsvg_path%')
        ->arg('$InkscapePath', '%app.inkscape_path%')
        ->arg('$Python3Path', '%app.python3_path%');

    $services->set(FileHandler::class)
        ->arg('$DataDir', '%app.data_directory%');

    $services->set(BackupGenerationMessageHandler::class)
        ->arg('$accountRepository', service(AccountRepository::class))
        ->arg('$dataDirectory', '%app.data_directory%')
        ->arg('$databasePrefix', '%app.database_prefix%')
        ->arg('$runalyzeVersion', '%RUNALYZE_VERSION%');

    $services->set(PosterGenerationStateResolver::class);

    $services->set(PosterGenerationMessageHandler::class)
        ->arg('$logger', service('monolog.logger.queue'))
        ->arg('$accountRepository', service(AccountRepository::class))
        ->arg('$sportRepository', service(SportRepository::class))
        ->arg('$generateJsonData', service(GenerateJsonData::class))
        ->arg('$generatePoster', service(GeneratePoster::class))
        ->arg('$fileHandler', service(FileHandler::class))
        ->arg('$stateResolver', service(PosterGenerationStateResolver::class))
        ->arg('$dataDirectory', '%app.data_directory%')
        ->arg('$rsvgPath', '%app.rsvg_path%');

    $services->set(Feed::class);

    $services->set(Client::class);

    $services->set(FitConverter::class)
        ->arg('$PathToPerl', '%app.perl_path%')
        ->arg('$PathToFitPerlScript', '%kernel.project_dir%/call/perl/fittorunalyze.pl');

    $services->set(TtbinConverter::class)
        ->arg('$PathToConverter', '%kernel.project_dir%/%app.ttbin_path%');

    $services->set(FileImporter::class)
        ->arg('$fitConverter', service(FitConverter::class))
        ->arg('$ttbinConverter', service(TtbinConverter::class))
        ->arg('$DirectoryForFailedImports', '%app.data_directory%/import/activity/failed')
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'activity_uploads',
        ]);

    $services->set(ActivityDataContainerToActivityContextConverter::class);

    $services->set(ActivityDataContainerFilter::class)
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'activity_uploads',
        ]);

    $services->set(ActivityCache::class)
        ->arg('$Cache', service('app.cache.activity_uploads'));

    $services->set(ActivityContextAdapterFactory::class)
        ->arg('$WeatherForecast', service(WeatherForecast::class))
        ->arg('$TrainingRepository', service(TrainingRepository::class))
        ->arg('$routeNameEvalFactory', service('app.routename_evaluation.routename_evaluation_factory'));

    $services->set(DataSeriesRemover::class);

    $services->set(AgeGradeLookup::class);

    $services->set(DarkSky::class)
        ->arg('$ApiKey', '%app.darksky_api_key%')
        ->arg('$HttpClient', service(Client::class))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->set(OpenWeatherMap::class)
        ->arg('$ApiKey', '%app.openweathermap_api_key%')
        ->arg('$Proxy', '%app.weather_proxy%')
        ->arg('$HttpClient', service(Client::class))
        ->arg('$logger', service('logger'))
        ->arg('$maxAgeInSeconds', '%app.weather_max_age_for_current_conditions%')
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->set(MeteostatNet::class)
        ->arg('$ApiKey', '%app.meteostatnet_api_key%')
        ->arg('$Proxy', '%app.weather_proxy%')
        ->arg('$HttpClient', service(Client::class))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->set(DatabaseCache::class)
        ->arg('$Cache', service(WeathercacheRepository::class));

    $services->set(WeatherForecast::class)
        ->arg('$cache', service(DatabaseCache::class))
        ->arg('$openWeatherMap', service(OpenWeatherMap::class))
        ->arg('$meteostatNet', service(MeteostatNet::class))
        ->arg('$DatabaseCache', service(WeathercacheRepository::class));

    $services->set(GeoTiffReader::class)
        ->arg('$directory', '%app.data_directory%/srtm');

    $services->set(GeoTiff::class)
        ->arg('$Reader', service(GeoTiffReader::class));

    $services->set(Geonames::class)
        ->arg('$GeonamesUsername', '%app.geonames_username%')
        ->arg('$client', service(Client::class))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->set('app.elevation_correction.google_maps', GoogleMaps::class)
        ->arg('$HttpClient', service(Client::class))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(GoogleMaps::class, 'app.elevation_correction.google_maps');

    $services->set('app.elevation_correction', ElevationCorrection::class)
        ->arg('$geoTiff', service(GeoTiff::class))
        ->arg('$geonames', service(Geonames::class))
        ->arg('$googleMaps', service('app.elevation_correction.google_maps'));

    $services->alias(ElevationCorrection::class, 'app.elevation_correction');

    $services->set('app.routename_evaluation.routename_evaluation_factory', RouteNameEvalFactory::class)
        ->arg('$RouteNameEval', service('app.routename_evaluation.routename_evaluation_osm'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(RouteNameEvalFactory::class, 'app.routename_evaluation.routename_evaluation_factory');

    $services->set('app.routename_evaluation.routename_evaluation_osm', RouteNameEvalOsm::class)
        ->arg('$Url', '%app.osm_overpass_url%')
        ->arg('$Proxy', '%app.osm_overpass_proxy%')
        ->arg('$HttpClient', service(Client::class))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(RouteNameEvalOsm::class, 'app.routename_evaluation.routename_evaluation_osm');

    $services->set('app.timezone_lookup', TimezoneLookup::class)
        ->arg('$PathToTimeZoneDatabase', '%app.data_directory%/timezone.sqlite')
        ->arg('$SpatialiteExtensionName', '%app.sqlite_mod_spatialite%')
        ->arg('$SilentExceptions', true);

    $services->alias(TimezoneLookup::class, 'app.timezone_lookup');

    $services->set(SessionRequestProcessor::class)
        ->autowire(true)
        ->tag('monolog.processor', [
            'method' => 'processRecord',
        ]);


    $services->set(DisposableDomainProvider::class)
        ->arg('$projectDir', '%kernel.project_dir%');

    $services->alias('app.disposable_domain_provider', DisposableDomainProvider::class);

    $services->load('Runalyze\\Bundle\\CoreBundle\\Validator\\Constraints\\', __DIR__ . '/../src/CoreBundle/Validator/Constraints/*Validator.php');
};
