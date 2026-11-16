<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use InvalidArgumentException;
use LogicException;
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

        usort($plugins, static fn (array $left, array $right): int => ($right['active'] <=> $left['active'])
            ?: ($left['order'] <=> $right['order'])
            ?: ($left['id'] <=> $right['id']));

        return $plugins;
    }

    /**
     * @return list<Plugin>
     */
    public function getInstalledPlugins(?int $type = null, ?int $active = null): array
    {
        $plugins = [];

        foreach ($this->getInstalledPluginData() as $data) {
            if (($type !== null && $data['type'] !== PluginType::string($type)) || ($active !== null && $data['active'] !== $active)) {
                continue;
            }

            $plugins[] = $this->pluginInstantiator->instantiate($data['key'], $data['id'], $data);
        }

        return $plugins;
    }

    /**
     * @throws InvalidArgumentException if no plugin is installed with the given id
     * @throws LogicException if there is no authenticated account
     */
    public function getInstalledPluginById(int $id): Plugin
    {
        foreach ($this->getInstalledPluginData() as $data) {
            if ($data['id'] === $id) {
                return $this->pluginInstantiator->instantiate($data['key'], $id, $data);
            }
        }

        throw new InvalidArgumentException(sprintf('No plugin found for id "%d".', $id));
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
