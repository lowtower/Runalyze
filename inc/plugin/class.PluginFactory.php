<?php

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */

use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;

class PluginFactory
{
    private readonly LegacyPluginInstantiator $pluginInstantiator;

    public function __construct(
        ?LegacyPluginInstantiator $pluginInstantiator = null,
        private readonly ?PluginDiscovery $pluginDiscovery = null,
        private readonly ?PluginInstallationManager $pluginInstallationManager = null
    ) {
        $this->pluginInstantiator = $pluginInstantiator ?? new LegacyPluginInstantiator();
    }

    public const string CACHE_KEY = 'plugins';

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    private static array $Plugins = [];

    private static function Plugins(): array
    {
        $accountId = SessionAccountHandler::getId();
        self::$Plugins[$accountId] ??= self::fetchAllPlugins();

        return self::$Plugins[$accountId];
    }

    private static function fetchAllPlugins(): array
    {
        $data = Cache::get(self::CACHE_KEY);

        if ($data === null || $data == []) {
            $data = self::fetchAllPluginsFrom(DB::getInstance(), SessionAccountHandler::getId());
            Cache::set(self::CACHE_KEY, $data, 3600);
        }

        return $data;
    }

    public static function clearCache(): void
    {
        $accountId = SessionAccountHandler::getId();
        unset(self::$Plugins[$accountId]);
        Cache::delete(self::CACHE_KEY);
    }

    private static function fetchAllPluginsFrom(PDO $PDO, int $accountID): array
    {
        return $PDO->query(
            'SELECT * FROM `' . PREFIX . 'plugin` '
            . 'WHERE `accountid`=' . $accountID . ' '
            . 'ORDER BY `order` ASC'
        )->fetchAll();
    }

    public static function dataFor(int $id): array
    {
        foreach (self::Plugins() as $data) {
            if ($data['id'] == $id) {
                return $data;
            }
        }

        return [];
    }

    public static function allIDs(): array
    {
        $IDs = [];

        foreach (self::Plugins() as $data) {
            $IDs[] = $data['id'];
        }

        return $IDs;
    }

    /**
     * @throws LogicException if there is no authenticated account
     */
    public function completeData(?int $type = null): array
    {
        if ($type === null) {
            return $this->getPluginData();
        }

        $plugins = $this->getPluginData();
        foreach ($plugins as $k => $plugin) {
            if ($plugin['type'] != PluginType::string($type)) {
                unset($plugins[$k]);
            }
        }

        return $plugins;
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException if there is no authenticated account
     */
    public function newInstance(string $pluginkey): Plugin
    {
        foreach ($this->getPluginData() as $plugin) {
            if ($plugin['key'] == $pluginkey) {
                $pluginId = $plugin['id'];

                if (! is_int($pluginId) && ! is_string($pluginId)) {
                    throw new InvalidArgumentException(sprintf('Plugin "%s" has an invalid identifier.', $pluginkey));
                }

                return $this->pluginInstantiator->instantiate($pluginkey, $pluginId);
            }
        }

        throw new InvalidArgumentException('Plugin with key "' . $pluginkey . '" is not installed.');
    }

    /**
     * @throws InvalidArgumentException if no plugin is installed with the given id
     * @throws LogicException if there is no authenticated account
     */
    public function newInstanceFor(int $id): Plugin
    {
        foreach ($this->getPluginData() as $plugin) {
            if ((int) $plugin['id'] === $id) {
                return $this->newInstance((string) $plugin['key']);
            }
        }

        throw new InvalidArgumentException('No plugin found for id "' . $id . '".');
    }

    /**
     * @throws InvalidArgumentException if the class is not an instantiable plugin implementation
     */
    public function newInstallerInstance(string $Pluginkey): Plugin
    {
        return $this->pluginInstantiator->instantiate($Pluginkey, PluginInstaller::ID);
    }

    /**
     * @throws \LogicException if there is no authenticated account
     */
    public function isInstalled(string $key): bool
    {
        if ($this->pluginInstallationManager instanceof PluginInstallationManager) {
            return $this->pluginInstallationManager->isInstalled($key);
        }

        return in_array($key, $this->allPlugins(), true);
    }

    /**
     * @param mixed $type false or enum
     * @return array array with plugin keys
     *
     * @throws LogicException if there is no authenticated account
     */
    public function allPlugins($type = false): array
    {
        return $this->getPlugins($type);
    }

    /**
     * @param mixed $type false or enum
     * @return array array with plugin keys
     *
     * @throws LogicException if there is no authenticated account
     */
    public function activePlugins($type = false): array
    {
        return $this->getPlugins($type, Plugin::ACTIVE);
    }

    /**
     * @param mixed $type false or enum
     * @return array array with plugin keys
     *
     * @throws LogicException if there is no authenticated account
     */
    public function inactivePlugins($type = false): array
    {
        return $this->getPlugins($type, Plugin::ACTIVE_NOT);
    }

    /**
     * @return array array with plugin keys
     *
     * @throws LogicException if there is no authenticated account
     */
    public function variousPlugins(): array
    {
        return $this->getPlugins(PluginType::STAT, Plugin::ACTIVE_VARIOUS);
    }

    /**
     * @return array array with plugin keys
     *
     * @throws LogicException if there is no authenticated account
     */
    public function enabledPanels(): array
    {
        $keys = [];

        foreach ($this->getPluginData() as $plugin) {
            if ($plugin['type'] == PluginType::string(PluginType::PANEL) && $plugin['active'] != Plugin::ACTIVE_NOT) {
                $keys[] = $plugin['key'];
            }
        }

        return $keys;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws LogicException if there is no authenticated account
     */
    private function getPluginData(): array
    {
        if ($this->pluginInstallationManager instanceof PluginInstallationManager) {
            $accountId = SessionAccountHandler::getId();

            self::$Plugins[$accountId] = $this->pluginInstallationManager->getInstalledPluginData();

            return self::$Plugins[$accountId];
        }

        return self::Plugins();
    }

    /**
     * @param mixed $type false or enum
     * @param mixed $active false or enum
     * @return array array with plugin keys
     *
     * @throws LogicException if there is no authenticated account
     */
    protected function getPlugins($type = false, $active = false): array
    {
        $keys = [];

        foreach ($this->getPluginData() as $plugin) {
            if (($type === false || $plugin['type'] == PluginType::string($type)) && ($active === false || $plugin['active'] == $active)) {
                $keys[] = $plugin['key'];
            }
        }

        return $keys;
    }

    /**
     * @return array<int, array{key: string}>
     *
     * @throws \LogicException if there is no authenticated account
     * @throws RuntimeException if the plugin directory cannot be read
     */
    public function notInstalledPlugins(): array
    {
        $plugins = [];

        $discovery = $this->pluginDiscovery ?? new PluginDiscovery(FRONTEND_PATH . '../plugin/');

        $installedPluginKeys = $this->pluginInstallationManager?->getInstalledPluginKeys();

        foreach ($discovery->findAvailablePluginKeys() as $key) {
            $isInstalled = $installedPluginKeys !== null
                ? in_array($key, $installedPluginKeys, true)
                : $this->isInstalled($key);

            if (! $isInstalled) {
                $plugins[] = [
                    'key' => $key,
                ];
            }
        }

        return $plugins;
    }

    /**
     * @throws InvalidArgumentException if the class is not an instantiable plugin implementation
     * @throws \LogicException if there is no authenticated account
     */
    public function installPlugin(string $Pluginkey): bool
    {
        if ($this->pluginInstallationManager instanceof PluginInstallationManager) {
            $installed = $this->pluginInstallationManager->install($Pluginkey);

            if ($installed) {
                self::clearCache();
            }

            return $installed;
        }

        return $this->newInstallerInstance($Pluginkey)->install();
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function keyFor(int $id): string
    {
        foreach (self::Plugins() as $plugin) {
            if ($id == $plugin['id']) {
                return $plugin['key'];
            }
        }

        throw new InvalidArgumentException('No plugin found for id "' . $id . '".');
    }
}
