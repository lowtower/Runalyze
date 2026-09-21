<?php

/**
 * This file contains class::PluginFactory
 * @package Runalyze\Plugin
 */
/**
 * Plugin factory
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginFactory
{
    /**
     * @var string
     */
    public const CACHE_KEY = 'plugins';

    /**
     * Array with all keys
     */
    private static ?array $Plugins = null;

    /**
     * Array with complete plugin data
     */
    private static function Plugins(): array
    {
        self::$Plugins ??= self::fetchAllPlugins();

        return self::$Plugins;
    }

    /**
     * Cache all from Table plugin for a user
     */
    private static function fetchAllPlugins()
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
        self::$Plugins = null;
        Cache::delete(self::CACHE_KEY);
    }

    /**
     * Fetch complete list from database
     */
    private static function fetchAllPluginsFrom(PDO $PDO, int $accountID): array
    {
        return $PDO->query(
            'SELECT * FROM `' . PREFIX . 'plugin` ' .
            'WHERE `accountid`=' . $accountID . ' ' .
            'ORDER BY `order` ASC'
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
     * Complete plugin data
     * @param string|bool $type [optional]
     */
    public function completeData($type = false): array
    {
        if ($type === false) {
            return self::Plugins();
        }

        $plugins = self::Plugins();
        foreach ($plugins as $k => $plugin) {
            if ($plugin['type'] != PluginType::string((int) $type)) {
                unset($plugins[$k]);
            }
        }

        return $plugins;
    }

    /**
     * New instance for key
     * @throws \InvalidArgumentException
     */
    public function newInstance(string $pluginkey): Plugin
    {
        foreach (self::Plugins() as $plugin) {
            if ($plugin['key'] == $pluginkey) {
                return new $pluginkey($plugin['id']);
            }
        }

        throw new InvalidArgumentException('Plugin with key "' . $pluginkey . '" is not installed.');
    }

    /**
     * New instance for id
     */
    public function newInstanceFor(int $id): Plugin
    {
        return $this->newInstance(self::keyFor($id));
    }

    /**
     * New instance for key
     */
    public function newInstallerInstance(string $Pluginkey): Plugin
    {
        return new $Pluginkey(PluginInstaller::ID);
    }

    /**
     * Is the plugin already installed?
     */
    public function isInstalled(string $key): bool
    {
        return in_array($key, $this->allPlugins());
    }

    /**
     * Get all plugins
     * @param mixed $type [optional] false or enum
     * @return array array with plugin keys
     */
    public function allPlugins($type = false): array
    {
        return $this->getPlugins($type);
    }

    /**
     * Get all active plugins
     * @param mixed $type [optional] false or enum
     * @return array array with plugin keys
     */
    public function activePlugins($type = false): array
    {
        return $this->getPlugins($type, Plugin::ACTIVE);
    }

    /**
     * Get all inactive plugins
     * @param mixed $type [optional] false or enum
     * @return array array with plugin keys
     */
    public function inactivePlugins($type = false): array
    {
        return $this->getPlugins($type, Plugin::ACTIVE_NOT);
    }

    /**
     * Get all various plugins
     * @return array array with plugin keys
     */
    public function variousPlugins(): array
    {
        return $this->getPlugins(PluginType::STAT, Plugin::ACTIVE_VARIOUS);
    }

    /**
     * Get all enabled panels
     * @return array array with plugin keys
     */
    public function enabledPanels(): array
    {
        $keys = [];

        foreach (self::Plugins() as $plugin) {
            if ($plugin['type'] == PluginType::string(PluginType::PANEL) && $plugin['active'] != Plugin::ACTIVE_NOT) {
                $keys[] = $plugin['key'];
            }
        }

        return $keys;
    }

    /**
     * @param mixed $type [optional] false or enum
     * @param mixed $active [optional] false or enum
     * @return array array with plugin keys
     */
    protected function getPlugins($type = false, $active = false): array
    {
        $keys = [];

        foreach (self::Plugins() as $plugin) {
            if (($type === false || $plugin['type'] == PluginType::string($type)) && ($active === false || $plugin['active'] == $active)) {
                $keys[] = $plugin['key'];
            }
        }

        return $keys;
    }

    /**
     * Get uninstalled plugins
     */
    public function notInstalledPlugins(): array
    {
        $plugins = [];
        $dir = opendir(FRONTEND_PATH . '../plugin/');

        while ($file = readdir($dir)) {
            if ($file[0] != '.' && is_dir(FRONTEND_PATH . '../plugin/' . $file) && ! $this->isInstalled($file)) {
                $plugins[] = [
                    'key' => $file,
                ];
            }
        }

        closedir($dir);

        return $plugins;
    }

    /**
     * Install a new plugin
     */
    public function installPlugin(string $Pluginkey)
    {
        $Plugin = new $Pluginkey(PluginInstaller::ID);
        $Plugin->key = $Pluginkey;

        return $Plugin->install();
    }

    /**
     * Get the PLUGINKEY for a given ID from database
     * @throws \InvalidArgumentException
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
