<?php

/**
 * @author Hannes Christiansen
 * @package Runalyze\Plugin
 */

use Runalyze\Bundle\CoreBundle\Services\LegacyPluginInstantiator;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;

class PluginFactory
{
    private readonly LegacyPluginInstantiator $pluginInstantiator;

    public function __construct(
        ?LegacyPluginInstantiator $pluginInstantiator = null,
        private readonly ?PluginInstallationManager $pluginInstallationManager = null
    ) {
        $this->pluginInstantiator = $pluginInstantiator ?? new LegacyPluginInstantiator();
    }

    public const string CACHE_KEY = 'plugins';

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    private static array $Plugins = [];

    private function Plugins(): array
    {
        $accountId = SessionAccountHandler::getId();
        self::$Plugins[$accountId] ??= $this->fetchAllPlugins();

        return self::$Plugins[$accountId];
    }

    private function fetchAllPlugins(): array
    {
        $data = Cache::get(self::CACHE_KEY);

        if ($data === null || $data == []) {
            $data = $this->fetchAllPluginsFrom(DB::getInstance(), SessionAccountHandler::getId());
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

    private function fetchAllPluginsFrom(PDO $PDO, int $accountID): array
    {
        return $PDO->query(
            'SELECT * FROM `' . PREFIX . 'plugin` '
            . 'WHERE `accountid`=' . $accountID . ' '
            . 'ORDER BY `order` ASC'
        )->fetchAll();
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

                return $this->pluginInstantiator->instantiate($pluginkey, $pluginId, $plugin);
            }
        }

        throw new InvalidArgumentException('Plugin with key "' . $pluginkey . '" is not installed.');
    }

    /**
     * @throws \LogicException if there is no authenticated account
     */
    public function isInstalled(string $key): bool
    {
        if ($this->pluginInstallationManager instanceof PluginInstallationManager) {
            return $this->pluginInstallationManager->isInstalled($key);
        }

        return in_array($key, array_column($this->getPluginData(), 'key'), true);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws LogicException if there is no authenticated account
     */
    private function getPluginData(): array
    {
        if ($this->pluginInstallationManager instanceof PluginInstallationManager) {
            return $this->pluginInstallationManager->getInstalledPluginData();
        }

        return $this->Plugins();
    }

}
