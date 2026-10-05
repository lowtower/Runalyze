<?php

/**
 * This file contains the class::Frontend to create and print the HTML-Page.
 * @package Runalyze\Frontend
 */

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Configuration;
use Runalyze\Timezone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Frontend class for setting up everything
 *
 * The frontend initializes everything for Runalyze.
 * It sets the autoloader, constants and mysql-connection.
 * By default, constructing a new frontend will print a html-header.
 *
 * Standard initialization of Runalyze:
 * <code>
 *  require 'inc/class.Frontend.php';
 *  $Frontend = new Frontend();
 * </code>
 *
 * @author Hannes Christiansen
 * @package Runalyze\Frontend
 */
class Frontend
{
    protected array $yamlConfig = [];

    protected bool $HideFooter = false;

    /**
     * Constructing a new Frontend includes all files and sets the correct header.
     * Runalyze is not usable without setting up the environment with this class.
     *
     * @param bool $hideHeaderAndFooter By default a html-header is directly shown
     * @throws \RuntimeException if the legacy cache directory cannot be initialized
     */
    public function __construct(
        bool $hideHeaderAndFooter = false,
        protected ?TokenStorageInterface $symfonyToken = null,
        protected ?ParameterBagInterface $parameterBag = null
    ) {
        $this->initSystem();
        $this->defineConsts();

        if (! $hideHeaderAndFooter) {
            $this->displayHeader();
        } else {
            $this->HideFooter = true;
        }
    }

    public function __destruct()
    {
        if (! $this->HideFooter) {
            $this->displayFooter();
        }
    }

    private function defineConstantIfMissing(string $name, mixed $value): void
    {
        if (! defined($name)) {
            define($name, $value);
        }
    }

    /**
     * @throws \RuntimeException if the legacy cache directory cannot be initialized
     */
    private function initSystem(): void
    {
        $this->defineConstantIfMissing('RUNALYZE', true);
        $this->defineConstantIfMissing('FRONTEND_PATH', __DIR__ . '/');

        $this->setAutoloader();
        $this->initCache();
        $this->initConfig();
        $this->initDatabase();
        $this->initSessionAccountHandler();
        $this->initTimezone();
        $this->forwardAccountIDtoDatabaseWrapper();
    }

    private function setAutoloader(): void
    {
        require_once FRONTEND_PATH . '../vendor/autoload.php';
    }

    private function initConfig(): void
    {
        // Get parameters from the parameter bag
        $this->yamlConfig = [
            'RUNALYZE_VERSION' => $this->parameterBag->get('RUNALYZE_VERSION'),
            'RUNALYZE_DATE' => $this->parameterBag->get('RUNALYZE_DATE'),
            'weather_proxy' => $this->parameterBag->get('app.weather_proxy'),
            'darksky_api_key' => $this->parameterBag->get('app.darksky_api_key'),
            'openweathermap_api_key' => $this->parameterBag->get('app.openweathermap_api_key'),
            'meteostatnet_api_key' => $this->parameterBag->get('app.meteostatnet_api_key'),
            'nokia_here_appid' => $this->parameterBag->get('app.nokia_here_appid'),
            'nokia_here_token' => $this->parameterBag->get('app.nokia_here_token'),
            'thunderforest_api_key' => $this->parameterBag->get('app.thunderforest_api_key'),
            'mapbox_api_key' => $this->parameterBag->get('app.mapbox_api_key'),
            'perl_path' => $this->parameterBag->get('app.perl_path'),
            'ttbin_path' => $this->parameterBag->get('app.ttbin_path'),
            'geonames_username' => $this->parameterBag->get('app.geonames_username'),
            'user_disable_account_activation' => $this->parameterBag->get('app.user_disable_account_activation'),
            'sqlite_mod_spatialite' => $this->parameterBag->get('app.sqlite_mod_spatialite'),
            'data_directory' => $this->parameterBag->get('app.data_directory'),
            'osm_overpass_url' => $this->parameterBag->get('app.osm_overpass_url'),
            'osm_overpass_proxy' => $this->parameterBag->get('app.osm_overpass_proxy'),
            'database_prefix' => $this->parameterBag->get('app.database_prefix'),
            'database_host' => $this->parameterBag->get('app.database_host'),
            'database_port' => $this->parameterBag->get('app.database_port'),
            'database_user' => $this->parameterBag->get('app.database_user'),
            'database_password' => $this->parameterBag->get('app.database_password'),
            'database_name' => $this->parameterBag->get('app.database_name'),
        ];

        // Define constants
        $this->defineConstantIfMissing('WEATHER_PROXY', $this->yamlConfig['weather_proxy']);
        $this->defineConstantIfMissing('DARKSKY_API_KEY', $this->yamlConfig['darksky_api_key']);
        $this->defineConstantIfMissing('OPENWEATHERMAP_API_KEY', $this->yamlConfig['openweathermap_api_key']);
        $this->defineConstantIfMissing('METEOSTATNET_API_KEY', $this->yamlConfig['meteostatnet_api_key']);
        $this->defineConstantIfMissing('NOKIA_HERE_APPID', $this->yamlConfig['nokia_here_appid']);
        $this->defineConstantIfMissing('NOKIA_HERE_TOKEN', $this->yamlConfig['nokia_here_token']);
        $this->defineConstantIfMissing('THUNDERFOREST_API_KEY', $this->yamlConfig['thunderforest_api_key']);
        $this->defineConstantIfMissing('MAPBOX_API_KEY', $this->yamlConfig['mapbox_api_key']);
        $this->defineConstantIfMissing('PERL_PATH', $this->yamlConfig['perl_path']);
        $this->defineConstantIfMissing('TTBIN_PATH', $this->yamlConfig['ttbin_path']);
        $this->defineConstantIfMissing('GEONAMES_USERNAME', $this->yamlConfig['geonames_username']);
        $this->defineConstantIfMissing('USER_DISABLE_ACCOUNT_ACTIVATION', $this->yamlConfig['user_disable_account_activation']);
        $this->defineConstantIfMissing('SQLITE_MOD_SPATIALITE', $this->yamlConfig['sqlite_mod_spatialite']);
        $this->defineConstantIfMissing('RUNALYZE_VERSION', $this->yamlConfig['RUNALYZE_VERSION']);
        $this->defineConstantIfMissing('RUNALYZE_DATE', $this->yamlConfig['RUNALYZE_DATE']);
        $this->defineConstantIfMissing('DATA_DIRECTORY', str_replace('%kernel.project_dir%', FRONTEND_PATH, $this->yamlConfig['data_directory']));
        $this->defineConstantIfMissing('OSM_OVERPASS_URL', $this->yamlConfig['osm_overpass_url']);
        $this->defineConstantIfMissing('OSM_OVERPASS_PROXY', $this->yamlConfig['osm_overpass_proxy']);
    }

    /**
     * Setup timezone
     */
    private function initTimezone(): void
    {
        Timezone::setPHPTimezone((int) SessionAccountHandler::getTimezone());
        Timezone::setMysql();
    }

    /**
     * Setup legacy cache
     *
     * @throws \RuntimeException if the legacy cache directory cannot be initialized
     */
    private function initCache(): void
    {
        require_once FRONTEND_PATH . '/system/class.Cache.php';

        try {
            new Cache();
        } catch (Exception $exception) {
            throw new \RuntimeException(
                sprintf('Cache directory "%s/cache/" must be writable.', Cache::PATH),
                0,
                $exception
            );
        }
    }

    /**
     * Init constants
     */
    private function defineConsts(): void
    {
        require_once FRONTEND_PATH . 'system/define.consts.php';

        Configuration::loadAll();

        \Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector::setGlobalFactor(Configuration::Data()->vo2maxCorrectionFactor());

        require_once FRONTEND_PATH . 'class.Helper.php';
    }

    /**
     * Connect to database
     */
    private function initDatabase(): void
    {
        $this->defineConstantIfMissing('PREFIX', $this->yamlConfig['database_prefix']);

        DB::connect($this->yamlConfig['database_host'], $this->yamlConfig['database_port'], $this->yamlConfig['database_user'], $this->yamlConfig['database_password'], $this->yamlConfig['database_name']);
    }

    protected function initSessionAccountHandler(): void
    {
        new SessionAccountHandler();

        if ($this->symfonyToken instanceof TokenStorageInterface && $this->symfonyToken->getToken() !== null && $this->symfonyToken->getToken()->getUser() != 'anon.') {
            /** @var Account $user */
            $user = $this->symfonyToken->getToken()
                ->getUser();

            SessionAccountHandler::setAccount([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'language' => $user->getLanguage(),
                'timezone' => $user->getTimezone(),
                'mail' => $user->getMail(),
                'gender' => $user->getGender(),
                'birthyear' => $user->getBirthyear(),
            ]);
        }
    }

    protected function forwardAccountIDtoDatabaseWrapper(): void
    {
        DB::getInstance()->setAccountID(SessionAccountHandler::getId());
    }

    /**
     * Display the HTML-Header
     */
    public function displayHeader(): void
    {
        if (! Request::isAjax() && ! isset($_GET['hideHtmlHeader'])) {
            include __DIR__ . '/tpl/tpl.Frontend.header.php';
        }
    }

    /**
     * Display the HTML-Footer
     */
    public function displayFooter(): void
    {
        if (! Request::isAjax() && ! isset($_GET['hideHtmlHeader'])) {
            include __DIR__ . '/tpl/tpl.Frontend.footer.php';
        }
    }
}
