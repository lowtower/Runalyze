<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Plugin;
use PluginInstaller;
use PluginType;

final readonly class PluginSettingsProvider
{
    public function __construct(
        private PluginInstallationManager $installationManager,
        private LegacyPluginInstantiator $pluginInstantiator,
        private PluginDiscovery $pluginDiscovery
    ) {}

    /**
     * @return list<array{id: int, key: string, type: string, active: int, order: int}>
     */
    public function getInstalledPluginData(): array
    {
        $plugins = $this->installationManager->getInstalledPluginData();

        usort($plugins, static function (array $left, array $right): int {
            return ($right['active'] <=> $left['active'])
                ?: ($left['order'] <=> $right['order'])
                ?: ($left['id'] <=> $right['id']);
        });

        return $plugins;
    }

    /**
     * @return list<Plugin>
     */
    public function getInstalledPlugins(?int $type = null): array
    {
        $plugins = [];

        foreach ($this->getInstalledPluginData() as $data) {
            if ($type !== null && $data['type'] !== PluginType::string($type)) {
                continue;
            }

            $plugins[] = $this->pluginInstantiator->instantiate($data['key'], $data['id'], $data);
        }

        return $plugins;
    }

    /**
     * @return list<Plugin>
     */
    public function getAvailablePluginsToInstall(): array
    {
        $installedKeys = $this->installationManager->getInstalledPluginKeys();
        $plugins = [];

        foreach ($this->pluginDiscovery->findAvailablePluginKeys() as $key) {
            if (! in_array($key, $installedKeys, true)) {
                $plugins[] = $this->pluginInstantiator->instantiate($key, PluginInstaller::ID);
            }
        }

        return $plugins;
    }
}
