<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client;
use Monolog\Formatter\LineFormatter;
use Runalyze\Bundle\CoreBundle\ArgumentResolver\AccountValueResolver;
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
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Conf;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Entity\EquipmentType;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Tag;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Entity\Weathercache;
use Runalyze\Bundle\CoreBundle\EntityListener\ActivityListener;
use Runalyze\Bundle\CoreBundle\EntityListener\EquipmentUpdateSubscriber;
use Runalyze\Bundle\CoreBundle\EntityListener\RaceResultListener;
use Runalyze\Bundle\CoreBundle\EntityListener\RouteListener;
use Runalyze\Bundle\CoreBundle\EntityListener\UserListener;
use Runalyze\Bundle\CoreBundle\EventListener\AccountLastActionListener;
use Runalyze\Bundle\CoreBundle\EventListener\ConsoleExceptionListener;
use Runalyze\Bundle\CoreBundle\EventListener\LocaleListener;
use Runalyze\Bundle\CoreBundle\EventListener\MaintenanceListener;
use Runalyze\Bundle\CoreBundle\EventListener\RecalculationTasksPerformerListener;
use Runalyze\Bundle\CoreBundle\EventListener\TablePrefixSubscriber;
use Runalyze\Bundle\CoreBundle\EventListener\UserLocaleListener;
use Runalyze\Bundle\CoreBundle\EventListener\UserTimezoneListener;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Form\BodyValuesType;
use Runalyze\Bundle\CoreBundle\Form\EquipmentCategoryType;
use Runalyze\Bundle\CoreBundle\Form\MultiImporterType;
use Runalyze\Bundle\CoreBundle\Form\RaceResultType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportType;
use Runalyze\Bundle\CoreBundle\Form\Settings\SportTypeType;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaType;
use Runalyze\Bundle\CoreBundle\Form\Tools\PosterType;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisType;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityEquipmentType;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityLapIntensityChoiceType;
use Runalyze\Bundle\CoreBundle\Form\Type\ActivityTypeChoiceType;
use Runalyze\Bundle\CoreBundle\Form\Type\DistanceType;
use Runalyze\Bundle\CoreBundle\Form\Type\ElevationType;
use Runalyze\Bundle\CoreBundle\Form\Type\EnergyKcalType;
use Runalyze\Bundle\CoreBundle\Form\Type\EnergyType;
use Runalyze\Bundle\CoreBundle\Form\Type\HrZoneBoundType;
use Runalyze\Bundle\CoreBundle\Form\Type\PowerType;
use Runalyze\Bundle\CoreBundle\Form\Type\SportChoiceType;
use Runalyze\Bundle\CoreBundle\Form\Type\TemperatureType;
use Runalyze\Bundle\CoreBundle\Form\Type\WeightType;
use Runalyze\Bundle\CoreBundle\Form\Type\WindSpeedType;
use Runalyze\Bundle\CoreBundle\MessageHandler\BackupGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\MessageHandler\PosterGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\ConfRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentRepository;
use Runalyze\Bundle\CoreBundle\Repository\EquipmentTypeRepository;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TagRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Repository\TypeRepository;
use Runalyze\Bundle\CoreBundle\Repository\UserRepository;
use Runalyze\Bundle\CoreBundle\Repository\WeathercacheRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountChecker;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Activity\AgeGradeLookup;
use Runalyze\Bundle\CoreBundle\Services\Activity\DataSeriesRemover;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityCache;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Runalyze\Bundle\CoreBundle\Services\Import\GeoTiffReader;
use Runalyze\Bundle\CoreBundle\Services\Import\TimezoneLookup;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Bundle\CoreBundle\Services\PrivacyGuard;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Services\SessionRequestProcessor;
use Runalyze\Bundle\CoreBundle\Services\Sha256SaltedEncoder;
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

    $parameters->set('app.garmin_api_key', '%env(string:GARMIN_API_KEY)%');

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

    $services->load('Runalyze\Bundle\CoreBundle\Controller\\', __DIR__ . '/../src/CoreBundle/Controller')
        ->tag('controller.service_arguments');

    $services->load('Runalyze\Bundle\GlossaryBundle\Controller\\', __DIR__ . '/../vendor/runalyze/glossary/Controller')
        ->tag('controller.service_arguments');

    $services->load('Runalyze\Bundle\CoreBundle\Command\\', __DIR__ . '/../src/CoreBundle/Command');

    $services->set(CleanupBackupCommand::class)
        ->arg('$parameterBag', service('parameter_bag'))
        ->arg('$filesystem', service('filesystem'));

    $services->set(InstallDatabaseCommand::class)
        ->arg('$parameterBag', service('parameter_bag'))
        ->arg('$doctrine', service('doctrine'));

    $services->set(InstallFilesystemCommand::class)
        ->arg('$filesystem', service('filesystem'))
        ->arg('$kernel', service('kernel'));

    $services->set(FinderFactory::class);

    $services->alias(FinderFactoryInterface::class, FinderFactory::class);

    $services->load('Runalyze\Migrations\\', __DIR__ . '/../migrations/*')
        ->bind('$DatabasePrefix', '%app.database_prefix%');

    $services->alias(EntityManager::class, 'doctrine.orm.default_entity_manager');
    $services->alias(ObjectManager::class, 'doctrine.orm.default_entity_manager');

    $services->set('app.value_resolver.account', AccountValueResolver::class)
        ->tag('controller.argument_value_resolver', [
            'priority' => 150,
        ]);

    $services->alias(AccountValueResolver::class, 'app.value_resolver.account');

    $services->set('app.listener.console_exception', ConsoleExceptionListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'console.error',
            'method' => 'onConsoleError',
        ]);

    $services->alias(ConsoleExceptionListener::class, 'app.listener.console_exception');

    $services->set('app.locale_listener', LocaleListener::class)
        ->args([
            '%kernel.default_locale%',
        ]);

    $services->alias(LocaleListener::class, 'app.locale_listener');

    $services->set('app.listener.user_timezone', UserTimezoneListener::class)
        ->arg('$token', service('security.token_storage'))
        ->tag('kernel.event_listener', [
            'event' => 'kernel.request',
            'method' => 'onKernelRequest',
        ]);

    $services->alias(UserTimezoneListener::class, 'app.listener.user_timezone');

    $services->set('app.user_locale_listener', UserLocaleListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'security.interactive_login',
            'method' => 'onInteractiveLogin',
        ])
        ->tag('kernel.event_listener', [
            'event' => 'security.switch_user',
            'method' => 'onSwitchUser',
        ]);

    $services->alias(UserLocaleListener::class, 'app.user_locale_listener');

    $services->set('app.account_last_action_listener', AccountLastActionListener::class)
        ->arg('$token', service('security.token_storage'))
        ->tag('kernel.event_listener', [
            'event' => 'kernel.controller',
            'method' => 'onCoreController',
        ]);

    $services->alias(AccountLastActionListener::class, 'app.account_last_action_listener');

    $services->set('app.listener.recalculation_tasks_performer_listener', RecalculationTasksPerformerListener::class)
        ->arg('$Manager', service('app.recalculation_manager'))
        ->tag('kernel.event_listener', [
            'event' => 'kernel.terminate',
            'method' => 'onTerminate',
        ]);

    $services->alias(RecalculationTasksPerformerListener::class, 'app.listener.recalculation_tasks_performer_listener');

    $services->set('app.sha256salted_encoder', Sha256SaltedEncoder::class);

    $services->alias(Sha256SaltedEncoder::class, 'app.sha256salted_encoder');

    $services->set(UnsubscribeHashGenerator::class)
        ->arg('$appSecret', '%app.secret%');

    $services->set('app.event.doctrine.table_prefix_subscriber', TablePrefixSubscriber::class)
        ->arg('$prefix', '%app.database_prefix%');

    $services->alias(TablePrefixSubscriber::class, 'app.event.doctrine.table_prefix_subscriber');

    $services->set('app.listener.maintenance', MaintenanceListener::class)
        ->arg('$parameterBag', service('parameter_bag'))
        ->arg('$kernel', service('kernel'))
        ->arg('$twig', service('twig'))
        ->tag('kernel.event_listener', [
            'event' => 'kernel.request',
            'method' => 'onKernelRequest',
        ]);

    $services->alias(MaintenanceListener::class, 'app.listener.maintenance');

    $services->set(AccountMailer::class)
        ->arg('$mailer', service('mailer'))
        ->arg('$twig', service('twig'))
        ->arg('$translator', service('translator'))
        ->call('setFrom', [
            '%app.mail_sender%',
            '%app.mail_name%',
        ]);

    $services->set('app.legacy_cache', LegacyCache::class)
        ->arg('$path', '%app.data_directory%');

    $services->alias(LegacyCache::class, 'app.legacy_cache');

    $services->set(LoadAccountData::class)
        ->arg('$passwordHasher', service('security.user_password_hasher'))
        ->tag('doctrine.fixture.orm');

    $services->set(Registration::class)
        ->arg('$passwordHasher', service('security.user_password_hasher'));

    $services->set(AccountChecker::class);

    $services->set('app.repository.account', AccountRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])
        ->arg(0, Account::class);

    $services->alias(AccountRepository::class, 'app.repository.account');

    $services->set('app.repository.conf', ConfRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Conf::class);

    $services->alias(ConfRepository::class, 'app.repository.conf');

    $services->set('app.repository.equipment', EquipmentRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Equipment::class);

    $services->alias(EquipmentRepository::class, 'app.repository.equipment');

    $services->set('app.repository.equipment_type', EquipmentTypeRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, EquipmentType::class);

    $services->alias(EquipmentTypeRepository::class, 'app.repository.equipment_type');

    $services->set(NotificationRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Notification::class);

    $services->set('app.repository.race_result', RaceresultRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Raceresult::class);

    $services->alias(RaceresultRepository::class, 'app.repository.race_result');

    $services->set('app.repository.sport', SportRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Sport::class);

    $services->alias(SportRepository::class, 'app.repository.sport');

    $services->set('app.repository.tag', TagRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Tag::class);

    $services->alias(TagRepository::class, 'app.repository.tag');

    $services->set('app.repository.type', TypeRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Type::class);

    $services->alias(TypeRepository::class, 'app.repository.type');

    $services->set('app.repository.training', TrainingRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Training::class);

    $services->alias(TrainingRepository::class, 'app.repository.training');

    $services->set(UserRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, User::class);

    $services->set('app.repository.weathercache', WeathercacheRepository::class)
        ->factory([
            service('doctrine.orm.entity_manager'),
            'getRepository',
        ])->arg(0, Weathercache::class);

    $services->alias(WeathercacheRepository::class, 'app.repository.weathercache');

    $services->set('app.entity_listener.activity', ActivityListener::class)
        ->arg('$RecalculationManager', service('app.recalculation_manager'))
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$EquipmentRepository', service('app.repository.equipment'))
        ->arg('$TimezoneLookup', service('app.timezone_lookup'))
        ->tag('doctrine.orm.entity_listener', [
            'lazy' => true,
        ]);

    $services->alias(ActivityListener::class, 'app.entity_listener.activity');

    $services->set('app.entity_subscriber.equipment_update', EquipmentUpdateSubscriber::class)
        ->tag('doctrine.event_listener', [
            'event' => 'onFlush',
            'connection' => 'default',
            'lazy' => true,
        ]);

    $services->alias(EquipmentUpdateSubscriber::class, 'app.entity_subscriber.equipment_update');

    $services->set('app.entity_listener.user', UserListener::class)
        ->arg('$ConfigurationUpdater', service('app.configuration_updater'))
        ->tag('doctrine.orm.entity_listener', [
            'lazy' => true,
        ]);

    $services->alias(UserListener::class, 'app.entity_listener.user');

    $services->set('app.entity_listener.race_result', RaceResultListener::class)
        ->arg('$RecalculationManager', service('app.recalculation_manager'))
        ->tag('doctrine.orm.entity_listener', [
            'lazy' => true,
        ]);

    $services->alias(RaceResultListener::class, 'app.entity_listener.race_result');

    $services->set('app.entity_listener.route', RouteListener::class)
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->arg('$ElevationCorrection', service('app.elevation_correction'))
        ->tag('doctrine.orm.entity_listener', [
            'lazy' => true,
        ]);

    $services->alias(RouteListener::class, 'app.entity_listener.route');

    $services->set('app.form.activity', ActivityType::class)
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->arg('$TagRepository', service('app.repository.tag'))
        ->tag('form.type');

    $services->alias(ActivityType::class, 'app.form.activity');

    $services->set('app.form.multi_importer', MultiImporterType::class)
        ->tag('form.type');

    $services->alias(MultiImporterType::class, 'app.form.multi_importer');

    $services->set('app.form.activity.equipment', ActivityEquipmentType::class)
        ->arg('$tokenStorage', service('security.token_storage'))
        ->call('setConfigurationManager', [
            service('app.configuration_manager'),
        ])
        ->tag('form.type');

    $services->alias(ActivityEquipmentType::class, 'app.form.activity.equipment');

    $services->set('app.form.anova', AnovaType::class)
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$TypeRepository', service('app.repository.type'))
        ->arg('$EquipmentTypeRepository', service('app.repository.equipment_type'))
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->tag('form.type');

    $services->alias(AnovaType::class, 'app.form.anova');

    $services->set('app.form.trend_analysis', TrendAnalysisType::class)
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$TypeRepository', service('app.repository.type'))
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->tag('form.type');

    $services->alias(TrendAnalysisType::class, 'app.form.trend_analysis');

    $services->set('app.form.poster', PosterType::class)
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$TrainingRepository', service('app.repository.training'))
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->tag('form.type');

    $services->alias(PosterType::class, 'app.form.poster');

    $services->set('app.form.equipment', \Runalyze\Bundle\CoreBundle\Form\EquipmentType::class)
        ->arg('$EquipmentRepository', service('app.repository.equipment_type'))
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->tag('form.type');

    $services->alias(\Runalyze\Bundle\CoreBundle\Form\EquipmentType::class, 'app.form.equipment');

    $services->set('app.form.equipment_category', EquipmentCategoryType::class)
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$TokenStorage', service('security.token_storage'))
        ->tag('form.type');

    $services->alias(EquipmentCategoryType::class, 'app.form.equipment_category');

    $services->set('app.form.sport_setting', SportType::class)
        ->arg('$TypeRepository', service('app.repository.type'))
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$EquipmentTypeRepository', service('app.repository.equipment_type'))
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->tag('form.type');

    $services->alias(SportType::class, 'app.form.sport_setting');

    $services->set('app.form.sport_types_setting', SportTypeType::class)
        ->arg('$TokenStorageInterface', service('security.token_storage'))
        ->tag('form.type');

    $services->alias(SportTypeType::class, 'app.form.sport_types_setting');

    $services->set('app.form.race_result', RaceResultType::class)
        ->tag('form.type');

    $services->alias(RaceResultType::class, 'app.form.race_result');

    $services->set('app.form.type.body_values', BodyValuesType::class)
        ->tag('form.type');

    $services->alias(BodyValuesType::class, 'app.form.type.body_values');

    $services->set('app.form.type.distance', DistanceType::class)
        ->arg('$distanceUnit', service('app.unit_system.distance'))
        ->tag('form.type');

    $services->alias(DistanceType::class, 'app.form.type.distance');

    $services->set('app.form.type.elevation', ElevationType::class)
        ->arg('$distanceUnit', service('app.unit_system.elevation'))
        ->tag('form.type');

    $services->alias(ElevationType::class, 'app.form.type.elevation');

    $services->set('app.form.type.energy', EnergyType::class)
        ->arg('$energyUnit', service('app.unit_system.energy'))
        ->tag('form.type');

    $services->alias(EnergyType::class, 'app.form.type.energy');

    $services->set('app.form.type.energy_kcal', EnergyKcalType::class)
        ->tag('form.type');

    $services->alias(EnergyKcalType::class, 'app.form.type.energy_kcal');

    $services->set('app.form.type.power', PowerType::class)
        ->tag('form.type');

    $services->alias(PowerType::class, 'app.form.type.power');

    $services->set('app.form.type.sport', SportChoiceType::class)
        ->arg('$tokenStorage', service('security.token_storage'))
        ->arg('$SportRepository', service('app.repository.sport'))
        ->call('setConfigurationManager', [
            service('app.configuration_manager'),
        ])
        ->tag('form.type');

    $services->alias(SportChoiceType::class, 'app.form.type.sport');

    $services->set('app.form.type.activity_type', ActivityTypeChoiceType::class)
        ->arg('$tokenStorage', service('security.token_storage'))
        ->call('setConfigurationManager', [
            service('app.configuration_manager'),
        ])
        ->tag('form.type');

    $services->alias(ActivityTypeChoiceType::class, 'app.form.type.activity_type');

    $services->set('app.form.type.activity_lapintensity', ActivityLapIntensityChoiceType::class)
        ->arg('$tokenStorage', service('security.token_storage'))
        ->call('setConfigurationManager', [
            service('app.configuration_manager'),
        ])
        ->tag('form.type');

    $services->alias(ActivityLapIntensityChoiceType::class, 'app.form.type.activity_lapintensity');

    $services->set('app.form.type.temperature', TemperatureType::class)
        ->arg('$temperatureUnit', service('app.unit_system.temperature'))
        ->tag('form.type');

    $services->alias(TemperatureType::class, 'app.form.type.temperature');

    $services->set('app.form.type.wind_speed', WindSpeedType::class)
        ->arg('$velocityUnit', service('app.unit_system.wind_speed'))
        ->tag('form.type');

    $services->alias(WindSpeedType::class, 'app.form.type.wind_speed');

    $services->set('app.form.type.weight', WeightType::class)
        ->arg('$weightUnit', service('app.unit_system.weight'))
        ->tag('form.type');

    $services->alias(WeightType::class, 'app.form.type.weight');

    $services->set('app.form.type.hrzonebound', HrZoneBoundType::class)
        ->args([])
        ->tag('form.type');

    $services->alias(HrZoneBoundType::class, 'app.form.type.hrzonebound');

    $services->set('app.automatic_reload_flag_setter', AutomaticReloadFlagSetter::class);

    $services->alias(AutomaticReloadFlagSetter::class, 'app.automatic_reload_flag_setter');

    $services->set('app.privacy_guard', PrivacyGuard::class)
        ->arg('$tokenStorage', service('security.token_storage'))
        ->arg('$ConfigurationManager', service('app.configuration_manager'));

    $services->alias(PrivacyGuard::class, 'app.privacy_guard');

    $services->set(ActivityContextFactory::class)
        ->arg('$EntityManager', service('doctrine.orm.entity_manager'));

    $services->alias('app.activity_context.factory', ActivityContextFactory::class);

    $services->set(ConfigurationManager::class)
        ->arg('$Repository', service('app.repository.conf'))
        ->arg('$tokenStorage', service('security.token_storage'));

    $services->alias('app.configuration_manager', ConfigurationManager::class);

    $services->set('app.configuration_updater', ConfigurationUpdater::class)
        ->arg('$Repository', service('app.repository.conf'))
        ->arg('$Manager', service('app.configuration_manager'))
        ->lazy(true);

    $services->alias(ConfigurationUpdater::class, 'app.configuration_updater');

    $services->set('app.recalculation_manager', RecalculationManager::class)
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->arg('$ConfigurationUpdater', service('app.configuration_updater'))
        ->arg('$TrainingRepository', service('app.repository.training'))
        ->arg('$RaceResultRepository', service('app.repository.race_result'))
        ->lazy(true);

    $services->alias(RecalculationManager::class, 'app.recalculation_manager');

    $services->set('app.sport_selection_factory', SportSelectionFactory::class)
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->arg('$tokenStorage', service('security.token_storage'));

    $services->alias(SportSelectionFactory::class, 'app.sport_selection_factory');

    $services->set('app.unit_system_factory', UnitSystemFactory::class);

    $services->alias(UnitSystemFactory::class, 'app.unit_system_factory');

    $services->set('app.unit_system.distance', AbstractDistanceUnit::class)
        ->factory([
            service('app.unit_system_factory'),
            'getDistanceUnit',
        ])
        ->args([
            service('app.configuration_manager'),
        ]);

    $services->set('app.unit_system.elevation', AbstractDistanceUnit::class)
        ->factory([
            service('app.unit_system_factory'),
            'getElevationUnit',
        ])
        ->args([
            service('app.configuration_manager'),
        ]);

    $services->alias(AbstractDistanceUnit::class, 'app.unit_system.elevation');

    $services->set('app.unit_system.energy', AbstractEnergyUnit::class)
        ->factory([
            service('app.unit_system_factory'),
            'getEnergyUnit',
        ])
        ->args([
            service('app.configuration_manager'),
        ]);

    $services->alias(AbstractEnergyUnit::class, 'app.unit_system.energy');

    $services->set('app.unit_system.temperature', AbstractTemperatureUnit::class)
        ->factory([
            service('app.unit_system_factory'),
            'getTemperatureUnit',
        ])
        ->args([
            service('app.configuration_manager'),
        ]);

    $services->alias(AbstractTemperatureUnit::class, 'app.unit_system.temperature');

    $services->set('app.unit_system.wind_speed', AbstractPaceInDecimalFormatUnit::class)
        ->factory([
            service('app.unit_system_factory'),
            'getWindSpeedUnit',
        ])
        ->args([
            service('app.configuration_manager'),
        ]);

    $services->alias(AbstractPaceInDecimalFormatUnit::class, 'app.unit_system.wind_speed');

    $services->set('app.unit_system.weight', AbstractWeightUnit::class)
        ->factory([
            service('app.unit_system_factory'),
            'getWeightUnit',
        ])
        ->args([
            service('app.configuration_manager'),
        ]);

    $services->alias(AbstractWeightUnit::class, 'app.unit_system.weight');

    $containerConfigurator->extension('twig', [
        'globals' => [
            'locales' => '%locales%',
        ],
    ]);

    $services->set('app.twig_configuration_extension', ConfigurationExtension::class)
        ->private()
        ->arg('$ConfigurationManager', service('app.configuration_manager'))
        ->tag('twig.attribute_extension');

    $services->alias(ConfigurationExtension::class, 'app.twig_configuration_extension');

    $services->set('app.twig_html_extension', HtmlExtension::class)
        ->private()
        ->tag('twig.attribute_extension');

    $services->alias(HtmlExtension::class, 'app.twig_html_extension');

    $services->set('app.twig_utility_extension', UtilityExtension::class)
        ->private()
        ->tag('twig.attribute_extension');

    $services->alias(UtilityExtension::class, 'app.twig_utility_extension');

    $services->set('app.twig_value_extension', ValueExtension::class)
        ->private()
        ->arg('$configurationManager', service('app.configuration_manager'))
        ->tag('twig.attribute_extension');

    $services->alias(ValueExtension::class, 'app.twig_value_extension');

    $services->set('app.twig_automatic_reload_flag_extension', AutomaticReloadFlagExtension::class)
        ->private()
        ->tag('twig.attribute_extension');

    $services->alias(AutomaticReloadFlagExtension::class, 'app.twig_automatic_reload_flag_extension');

    $services->set('app.poster.generate_json', GenerateJsonData::class)
        ->arg('$TrainingRepository', service('app.repository.training'))
        ->arg('$RaceresultRepository', service('app.repository.race_result'))
        ->arg('$KernelRootDir', '%kernel.project_dir%/..');

    $services->alias(GenerateJsonData::class, 'app.poster.generate_json');

    $services->set('app.poster.generate_poster', GeneratePoster::class)
        ->arg('$KernelRootDir', '%kernel.project_dir%/..')
        ->arg('$Python3Path', '%app.python3_path%')
        ->arg('$TrainingRepository', service('app.repository.training'));

    $services->alias(GeneratePoster::class, 'app.poster.generate_poster');

    $services->set('app.poster.availability', Availability::class)
        ->arg('$RsvgPath', '%app.rsvg_path%')
        ->arg('$InkscapePath', '%app.inkscape_path%')
        ->arg('$Python3Path', '%app.python3_path%');

    $services->alias(Availability::class, 'app.poster.availability');

    $services->set('app.poster.filehandler', FileHandler::class)
        ->arg('$DataDir', '%app.data_directory%');

    $services->alias(FileHandler::class, 'app.poster.filehandler');

    $services->set(BackupGenerationMessageHandler::class)
        ->arg('$accountRepository', service('app.repository.account'))
        ->arg('$parameterBag', service('parameter_bag'))
        ->arg('$dataDirectory', '%app.data_directory%')
        ->arg('$databasePrefix', '%app.database_prefix%')
        ->arg('$runalyzeVersion', '%RUNALYZE_VERSION%');

    $services->set(PosterGenerationStateResolver::class);

    $services->set(PosterGenerationMessageHandler::class)
        ->arg('$logger', service('monolog.logger.queue'))
        ->arg('$accountRepository', service('app.repository.account'))
        ->arg('$sportRepository', service('app.repository.sport'))
        ->arg('$generateJsonData', service('app.poster.generate_json'))
        ->arg('$generatePoster', service('app.poster.generate_poster'))
        ->arg('$fileHandler', service('app.poster.filehandler'))
        ->arg('$stateResolver', service(PosterGenerationStateResolver::class))
        ->arg('$dataDirectory', '%app.data_directory%')
        ->arg('$rsvgPath', '%app.rsvg_path%')
        ->arg('$inkscapePath', '%app.inkscape_path%');

    $services->set('app.activity.feed', Feed::class);

    $services->alias(Feed::class, 'app.activity.feed');

    $services->set('app.http_client', Client::class);

    $services->alias(Client::class, 'app.http_client');

    $services->set('app.file_converter.fit', FitConverter::class)
        ->arg('$PathToPerl', '%app.perl_path%')
        ->arg('$PathToFitPerlScript', '%kernel.project_dir%/call/perl/fittorunalyze.pl');

    $services->alias(FitConverter::class, 'app.file_converter.fit');

    $services->set('app.file_converter.ttbin', TtbinConverter::class)
        ->arg('$PathToConverter', '%kernel.project_dir%/%app.ttbin_path%');

    $services->alias(TtbinConverter::class, 'app.file_converter.ttbin');

    $services->set('app.file_importer', FileImporter::class)
        ->arg('$fitConverter', service('app.file_converter.fit'))
        ->arg('$ttbinConverter', service('app.file_converter.ttbin'))
        ->arg('$DirectoryForFailedImports', '%app.data_directory%/import/activity/failed')
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'activity_uploads',
        ]);

    $services->alias(FileImporter::class, 'app.file_importer');

    $services->set('app.activity_duplicate_finder', DuplicateFinder::class)
        ->arg('$TrainingRepository', service('app.repository.training'));

    $services->alias(DuplicateFinder::class, 'app.activity_duplicate_finder');

    $services->set('app.activity_data_container.converter', ActivityDataContainerToActivityContextConverter::class)
        ->arg('$SportRepository', service('app.repository.sport'))
        ->arg('$TypeRepository', service('app.repository.type'))
        ->arg('$EquipmentRepository', service('app.repository.equipment'))
        ->arg('$ConfigurationManager', service('app.configuration_manager'));

    $services->alias(ActivityDataContainerToActivityContextConverter::class, 'app.activity_data_container.converter');

    $services->set('app.activity_data_container.filter', ActivityDataContainerFilter::class)
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'activity_uploads',
        ]);

    $services->alias(ActivityDataContainerFilter::class, 'app.activity_data_container.filter');

    $services->set('app.activity_context.cache', ActivityCache::class)
        ->arg('$Cache', service('app.cache.activity_uploads'));

    $services->alias(ActivityCache::class, 'app.activity_context.cache');

    $services->set('app.activity_context_adapter_factory', ActivityContextAdapterFactory::class)
        ->arg('$WeatherForecast', service('app.weather_forecast'))
        ->arg('$DuplicateFinder', service('app.activity_duplicate_finder'))
        ->arg('$routeNameEvalFactory', service('app.routename_elevation.routename_elevation_factory'));

    $services->alias(ActivityContextAdapterFactory::class, 'app.activity_context_adapter_factory');

    $services->set('app.data_series_remover', DataSeriesRemover::class)
        ->arg('$EntityManager', service('doctrine.orm.entity_manager'));

    $services->alias(DataSeriesRemover::class, 'app.data_series_remover');

    $services->set('app.age_grade_lookup', AgeGradeLookup::class)
        ->arg('$tokenStorage', service('security.token_storage'));

    $services->alias(AgeGradeLookup::class, 'app.age_grade_lookup');

    $services->set('app.weather_forecast.darksky', DarkSky::class)
        ->arg('$ApiKey', '%app.darksky_api_key%')
        ->arg('$HttpClient', service('app.http_client'))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(DarkSky::class, 'app.weather_forecast.darksky');

    $services->set('app.weather_forecast.openweathermap', OpenWeatherMap::class)
        ->arg('$ApiKey', '%app.openweathermap_api_key%')
        ->arg('$Proxy', '%app.weather_proxy%')
        ->arg('$HttpClient', service('app.http_client'))
        ->arg('$logger', service('logger'))
        ->arg('$maxAgeInSeconds', '%app.weather_max_age_for_current_conditions%')
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(OpenWeatherMap::class, 'app.weather_forecast.openweathermap');

    $services->set('app.weather_forecast.meteostatnet', MeteostatNet::class)
        ->arg('$ApiKey', '%app.meteostatnet_api_key%')
        ->arg('$Proxy', '%app.weather_proxy%')
        ->arg('$HttpClient', service('app.http_client'))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(MeteostatNet::class, 'app.weather_forecast.meteostatnet');

    $services->set('app.weather_forecast.cache', DatabaseCache::class)
        ->arg('$Cache', service('app.repository.weathercache'));

    $services->alias(DatabaseCache::class, 'app.weather_forecast.cache');

    $services->set('app.weather_forecast', WeatherForecast::class)
        ->arg('$cache', service('app.weather_forecast.cache'))
        ->arg('$openWeatherMap', service('app.weather_forecast.openweathermap'))
        ->arg('$meteostatNet', service('app.weather_forecast.meteostatnet'))
        ->arg('$DatabaseCache', service('app.repository.weathercache'));

    $services->alias(WeatherForecast::class, 'app.weather_forecast');

    $services->set('app.elevation_correction.geotiff_reader', GeoTiffReader::class)
        ->arg('$directory', '%app.data_directory%/srtm');

    $services->alias(GeoTiffReader::class, 'app.elevation_correction.geotiff_reader');

    $services->set('app.elevation_correction.geotiff', GeoTiff::class)
        ->arg('$Reader', service('app.elevation_correction.geotiff_reader'));

    $services->alias(GeoTiff::class, 'app.elevation_correction.geotiff');

    $services->set('app.elevation_correction.geonames', Geonames::class)
        ->arg('$GeonamesUsername', '%app.geonames_username%')
        ->arg('$client', service('app.http_client'))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(Geonames::class, 'app.elevation_correction.geonames');

    $services->set('app.elevation_correction.google_maps', GoogleMaps::class)
        ->arg('$HttpClient', service('app.http_client'))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(GoogleMaps::class, 'app.elevation_correction.google_maps');

    $services->set('app.elevation_correction', ElevationCorrection::class)
        ->arg('$geoTiff', service('app.elevation_correction.geotiff'))
        ->arg('$geonames', service('app.elevation_correction.geonames'))
        ->arg('$googleMaps', service('app.elevation_correction.google_maps'));

    $services->alias(ElevationCorrection::class, 'app.elevation_correction');

    $services->set('app.routename_elevation.routename_elevation_factory', RouteNameEvalFactory::class)
        ->arg('$RouteNameEval', service('app.routename_elevation.routename_elevation_osm'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(RouteNameEvalFactory::class, 'app.routename_elevation.routename_elevation_factory');

    $services->set('app.routename_elevation.routename_elevation_osm', RouteNameEvalOsm::class)
        ->arg('$Url', '%app.osm_overpass_url%')
        ->arg('$Proxy', '%app.osm_overpass_proxy%')
        ->arg('$HttpClient', service('app.http_client'))
        ->arg('$logger', service('logger'))
        ->tag('monolog.logger', [
            'channel' => 'external_services',
        ]);

    $services->alias(RouteNameEvalOsm::class, 'app.routename_elevation.routename_elevation_osm');

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

    $services->set('monolog.formatter.session_request', LineFormatter::class)
        ->arg('$format', '[%%datetime%%] [%%extra.token%%] %%channel%%.%%level_name%%: %%message%% %%context%% %%extra%%
');

    $services->set('app.frontend', Frontend::class)
        ->arg('$parameterBag', service('parameter_bag'));

    # $services->load('Runalyze\Bundle\CoreBundle\\Component\\Tool\Backup\\', __DIR__ . '/../src/CoreBundle/Component/Tool/Backup/');
};
