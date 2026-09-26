<?php

/**
 * This file contains class::PluginFactory
 * @package Runalyze\Plugin
 */

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */
class PluginFactory
{
    /**
     * @var string
     */
    public const CACHE_KEY = 'plugins';

    private static ?array $Plugins = null;

    private static function Plugins(): array
    {
        self::$Plugins ??= self::fetchAllPlugins();

        return self::$Plugins;
    }

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

    public function completeData(?int $type = null): array
    {
        if ($type === null) {
            return self::Plugins();
        }

        $plugins = self::Plugins();
        foreach ($plugins as $k => $plugin) {
            if ($plugin['type'] != PluginType::string($type)) {
                unset($plugins[$k]);
            }
        }

        return $plugins;
    }

    /**
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

    public function newInstanceFor(int $id): Plugin
    {
        return $this->newInstance(self::keyFor($id));
    }

    public function newInstallerInstance(string $Pluginkey): Plugin
    {
        return new $Pluginkey(PluginInstaller::ID);
    }

    public function isInstalled(string $key): bool
    {
        return in_array($key, $this->allPlugins());
    }

    /**
     * @param mixed $type false or enum
     * @return array array with plugin keys
     */
    public function allPlugins($type = false): array
    {
        return $this->getPlugins($type);
    }

    /**
     * @param mixed $type false or enum
     * @return array array with plugin keys
     */
    public function activePlugins($type = false): array
    {
        return $this->getPlugins($type, Plugin::ACTIVE);
    }

    /**
     * @param mixed $type false or enum
     * @return array array with plugin keys
     */
    public function inactivePlugins($type = false): array
    {
        return $this->getPlugins($type, Plugin::ACTIVE_NOT);
    }

    /**
     * @return array array with plugin keys
     */
    public function variousPlugins(): array
    {
        return $this->getPlugins(PluginType::STAT, Plugin::ACTIVE_VARIOUS);
    }

    /**
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
     * @param mixed $type false or enum
     * @param mixed $active false or enum
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

    public function installPlugin(string $Pluginkey)
    {
        $Plugin = new $Pluginkey(PluginInstaller::ID);

        return $Plugin->install();
    }

    /**
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
