<?php

/**
 * This file contains class::Plugin
 * @package Runalyze\Plugin
 */

use Runalyze\Configuration;

/**
 * Abstract class for Plugins
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
abstract class Plugin
{
    /**
     * Enum: inactive
     * @var int
     */
    public const ACTIVE_NOT = 0;

    /**
     * Enum: active
     * @var int
     */
    public const ACTIVE = 1;

    /**
     * Enum: various/hidden
     * @var int
     */
    public const ACTIVE_VARIOUS = 2;

    /**
     * Url for displaying the install-window
     * @var string
     */
    public static $INSTALL_URL = 'call/call.Plugin.install.php';

    /**
     * Url for displaying the config-window
     * @var string
     */
    public static $CONFIG_URL = 'call/call.Plugin.config.php';

    /**
     * Url for displaying the plugin
     * @var string
     */
    public static $DISPLAY_URL = 'my/plugin';

    /**
     * CSS-Flag for plugins: Don't reload if config has changed
     * @var string
     */
    public static $DONT_RELOAD_FOR_CONFIG_FLAG = 'dont-reload-for-config';

    /**
     * CSS-Flag for plugins: Don't reload if a training has changed
     * @var string
     */
    public static $DONT_RELOAD_FOR_TRAINING_FLAG = 'dont-reload-for-training';

    /**
     * Pluginkey
     */
    private string $key;

    /**
     * Integer flag: Is this statistic acitve?
     */
    private int $active;

    /**
     * Integer position of plugins
     */
    private int $order;

    /**
     * Internal sport-ID from database
     */
    protected int $sportid;

    /**
     * Displayed year (-1: 'all', 6/12: 'last 6/12 months')
     */
    protected int $year;

    /**
     * Internal data from database
     */
    protected string $dat;

    /**
     * Configuration
     */
    private PluginConfiguration $Configuration;

    /**
     * Constructor (needs ID)
     */
    public function __construct(
        /**
         * Internal ID from database
         */
        private int $id
    ) {
        $this->key = static::class;

        if ($this->id === PluginInstaller::ID) {
            return;
        }

        if ($this->id <= 0) {
            throw new RuntimeException('Invalid id "' . $this->id . '" given to create a plugin.');
        }

        $this->initConfiguration();
        $this->initVars();
        $this->initPlugin();
    }

    /**
     * May be used in subclass to set own configuration.
     * Make sure to add all values to the configuration object
     * before using <code>$this->setConfiguration($Configuration);</code>.
     */
    protected function initConfiguration()
    {
        $Configuration = new PluginConfiguration($this->id);

        $this->setConfiguration($Configuration);
    }

    protected function setConfiguration(PluginConfiguration $Configuration)
    {
        $this->Configuration = $Configuration;
    }

    /**
     * Configuration
     *
     * This method call will force the configuration object to catch its values
     * from the database if not already done.
     */
    final public function &Configuration(): PluginConfiguration
    {
        $this->Configuration->catchValuesFromDatabaseIfNotDoneYet();

        return $this->Configuration;
    }

    /**
     * Plugin key
     */
    final public function key(): string
    {
        return $this->key;
    }

    final public function id(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    abstract public function name();

    /**
     * @return string
     */
    abstract public function description();

    /**
     * @return int
     */
    abstract public function type();

    /**
     * Type as string
     */
    final public function typeString(): string
    {
        return PluginType::string($this->type());
    }

    /**
     * Is active?
     */
    final public function isActive(): bool
    {
        return $this->active === self::ACTIVE;
    }

    /**
     * Is inactive?
     */
    final public function isInActive(): bool
    {
        return $this->active === self::ACTIVE_NOT;
    }

    /**
     * Is hidden/various?
     */
    final public function isHidden(): bool
    {
        return $this->active === self::ACTIVE_VARIOUS;
    }

    final public function order(): int
    {
        return $this->order;
    }

    /**
     * Method for initializing everything (implemented in each plugin)
     */
    protected function initPlugin()
    {
    }

    /**
     * Includes the plugin-file for displaying the plugin (implemented in subclass)
     */
    abstract public function display();

    /**
     * Inits all data (implemented in each plugin)
     */
    protected function prepareForDisplay()
    {
    }

    /**
     * Method for displaying the content (implemented in each plugin)
     */
    abstract protected function displayContent();

    /**
     * Returns the html-link to this plugin for tab-navigation
     * @return string
     */
    abstract public function getLink();

    /**
     * Returns the html-link for inner-html-navigation
     * @param string $name displayed link-name
     * @param int $sport id of sport, default $this->sportid
     * @param int $year year, default $this->year
     * @param string $dat optional dat-parameter
     * @return string
     */
    abstract protected function getInnerLink(string $name, int $sport = 0, int $year = 0, string $dat = '');

    /**
     * Display description, can be overwritten for displaying a longer description
     */
    protected function displayLongDescription()
    {
        echo HTML::p($this->description());
    }

    /**
     * Install this plugin
     */
    final public function install(): bool
    {
        if ($this->id() !== PluginInstaller::ID) {
            return false;
        }

        $this->id = DB::getInstance()->insert('plugin', [
            'key',
            'type',
            'active',
            'order',
        ], [
            $this->key(),
            $this->typeString(),
            '1',
            '99',
        ]);

        return true;
    }

    /**
     * Initialize all variables
     */
    final protected function initVars()
    {
        if ($this->id() === PluginInstaller::ID) {
            return;
        }

        $data = PluginFactory::dataFor($this->id());
        $this->key = $data['key'];
        $this->active = $data['active'];
        $this->order = $data['order'];
        $this->sportid = $this->defaultSport();
        $this->year = $this->defaultYear();
        $this->dat = '';

        if (isset($_GET['sport'])) {
            if (is_numeric($_GET['sport'])) {
                $this->sportid = $_GET['sport'];
            }
        }

        if (isset($_GET['jahr'])) {
            if (is_numeric($_GET['jahr'])) {
                $this->year = $_GET['jahr'];
            }
        }

        if (isset($_GET['dat'])) {
            $this->dat = $_GET['dat'];
        }
    }

    final protected function showsAllYears(): bool
    {
        return $this->year === -1;
    }

    final protected function showsSpecificYear(): bool
    {
        return ! in_array($this->year, [-1, 6, 12], true);
    }

    final protected function showsTimeRange(): bool
    {
        return ($this->year === 6) || ($this->year === 12);
    }

    final protected function showsLast6Months(): bool
    {
        return $this->year === 6;
    }

    final protected function showsLast12Months(): bool
    {
        return $this->year === 12;
    }

    /**
     * May be overwritten in subclass.
     * Default setting: Configuration::General()->mainSport()
     *
     * @return int sportid, can be -1 for all sports
     */
    protected function defaultSport(): int
    {
        return Configuration::General()->mainSport();
    }

    /**
     * May be overwritten in subclass.
     * Default setting: current year
     *
     * @return int year, can be -1 for no year/comparison of all years
     */
    protected function defaultYear(): int
    {
        return (int) date('Y');
    }

    protected function titleForAllYears(): string
    {
        return __('Year on year');
    }

    /**
     * Get link for the config-window
     * @param string $name [optional], default: settings-icon
     * @param string $add_param [optional] additional parameter starting with '&...'
     */
    final public function getConfigLink(string $name = '', string $add_param = ''): ?string
    {
        if ($name === '') {
            $name = Icon::$CONF;
        }

        if (empty($this->Configuration()->objects())) {
            return null;
        }

        return Ajax::window('<a href="' . self::$CONFIG_URL . '?id=' . $this->id() . $add_param . '">' . $name . '</a>', 'small');
    }

    /**
     * Get reload-link
     */
    final public function getReloadLink(): string
    {
        return '<span class="link" onclick="Runalyze.reloadPlugin(\'' . $this->id() . '\');">' . Icon::$REFRESH_SMALL . '</span>';
    }

    /**
     * Handle Get/Post-data and update database
     */
    private function handleGetPostRequest()
    {
        if (isset($_GET['active'])) {
            $this->setActive((int) $_GET['active']);

            Ajax::setReloadFlag(Ajax::$RELOAD_ALL);
            echo Ajax::getReloadCommand();
        }
    }

    /**
     * Displays the config window for editing the variables
     */
    final public function displayConfigWindow()
    {
        $this->handleGetPostRequest();

        $Window = new PluginConfigurationWindow($this);
        $Window->display();
    }

    /**
     * Function to (in)activate the plugin
     */
    final public function setActive(int $active = 1)
    {
        DB::getInstance()->update('plugin', $this->id(), 'active', $active);
        $this->active = $active;

        PluginFactory::clearCache();
    }
}
