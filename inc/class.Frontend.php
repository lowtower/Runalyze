<?php

/**
 * This file contains the class::Frontend to create and print the HTML-Page.
 * @package Runalyze\Frontend
 */

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Configuration;
use Runalyze\Timezone;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
        protected ?ParameterBagInterface $parameterBag = null,
        protected ?RequestStack $requestStack = null
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
        $this->initRequestBridge();
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
        // Define constants directly from parameter bag
        $this->defineConstantIfMissing('RUNALYZE_VERSION', $this->parameterBag->get('RUNALYZE_VERSION'));
        $this->defineConstantIfMissing('RUNALYZE_DATE', $this->parameterBag->get('RUNALYZE_DATE'));

        $this->defineConstantIfMissing('WEATHER_PROXY', $this->parameterBag->get('app.weather_proxy'));
        $this->defineConstantIfMissing('DARKSKY_API_KEY', $this->parameterBag->get('app.darksky_api_key'));
        $this->defineConstantIfMissing('OPENWEATHERMAP_API_KEY', $this->parameterBag->get('app.openweathermap_api_key'));
        $this->defineConstantIfMissing('METEOSTATNET_API_KEY', $this->parameterBag->get('app.meteostatnet_api_key'));
        $this->defineConstantIfMissing('NOKIA_HERE_APPID', $this->parameterBag->get('app.nokia_here_appid'));
        $this->defineConstantIfMissing('NOKIA_HERE_TOKEN', $this->parameterBag->get('app.nokia_here_token'));
        $this->defineConstantIfMissing('THUNDERFOREST_API_KEY', $this->parameterBag->get('app.thunderforest_api_key'));
        $this->defineConstantIfMissing('MAPBOX_API_KEY', $this->parameterBag->get('app.mapbox_api_key'));
        $this->defineConstantIfMissing('PERL_PATH', $this->parameterBag->get('app.perl_path'));
        $this->defineConstantIfMissing('TTBIN_PATH', $this->parameterBag->get('app.ttbin_path'));
        $this->defineConstantIfMissing('GEONAMES_USERNAME', $this->parameterBag->get('app.geonames_username'));
        $this->defineConstantIfMissing('USER_DISABLE_ACCOUNT_ACTIVATION', $this->parameterBag->get('app.user_disable_account_activation'));
        $this->defineConstantIfMissing('SQLITE_MOD_SPATIALITE', $this->parameterBag->get('app.sqlite_mod_spatialite'));
        $this->defineConstantIfMissing('DATA_DIRECTORY', $this->parameterBag->get('app.data_directory'));
        $this->defineConstantIfMissing('OSM_OVERPASS_URL', $this->parameterBag->get('app.osm_overpass_url'));
        $this->defineConstantIfMissing('OSM_OVERPASS_PROXY', $this->parameterBag->get('app.osm_overpass_proxy'));
        $this->defineConstantIfMissing('DATABASE_PREFIX', $this->parameterBag->get('app.database_prefix'));
        $this->defineConstantIfMissing('DATABASE_HOST', $this->parameterBag->get('app.database_host'));
        $this->defineConstantIfMissing('DATABASE_PORT', $this->parameterBag->get('app.database_port'));
        $this->defineConstantIfMissing('DATABASE_USER', $this->parameterBag->get('app.database_user'));
        $this->defineConstantIfMissing('DATABASE_PASSWORD', $this->parameterBag->get('app.database_password'));
        $this->defineConstantIfMissing('DATABASE_NAME', $this->parameterBag->get('app.database_name'));
    }

    private function initTimezone(): void
    {
        Timezone::setPHPTimezone((int) SessionAccountHandler::getTimezone());
        Timezone::setMysql();
    }

    /**
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

    private function defineConsts(): void
    {
        require_once FRONTEND_PATH . 'system/define.consts.php';

        Configuration::loadAll();

        \Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector::setGlobalFactor(Configuration::Data()->vo2maxCorrectionFactor());

        require_once FRONTEND_PATH . 'class.Helper.php';
    }

    private function initDatabase(): void
    {
        $this->defineConstantIfMissing('PREFIX', $this->parameterBag->get('app.database_prefix'));

        DB::connect(
            $this->parameterBag->get('app.database_host'),
            (string) $this->parameterBag->get('app.database_port'),
            $this->parameterBag->get('app.database_user'),
            $this->parameterBag->get('app.database_password'),
            $this->parameterBag->get('app.database_name')
        );
    }

    private function initRequestBridge(): void
    {
        if ($this->requestStack instanceof RequestStack) {
            \Request::setRequestStack($this->requestStack);
        }
    }

    protected function initSessionAccountHandler(): void
    {
        new SessionAccountHandler();

        if ($this->symfonyToken instanceof TokenStorageInterface && $this->symfonyToken->getToken() instanceof TokenInterface && $this->symfonyToken->getToken()->getUser() != 'anon.') {
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

    public function displayHeader(): void
    {
        if (! Request::isAjax() && ! isset($_GET['hideHtmlHeader'])) {
            include __DIR__ . '/tpl/tpl.Frontend.header.php';
        }
    }

    public function displayFooter(): void
    {
        if (! Request::isAjax() && ! isset($_GET['hideHtmlHeader'])) {
            include __DIR__ . '/tpl/tpl.Frontend.footer.php';
        }
    }
}
