<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use InvalidArgumentException;
use LogicException;
use Plugin;
use PluginInstaller;
use PluginType;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelMetadataInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;

final readonly class PluginSettingsProvider
{
    public function __construct(
        private PluginInstallationManager $installationManager,
        private LegacyPluginInstantiator $pluginInstantiator,
        private PluginDiscovery $pluginDiscovery,
        private ?ModernPanelRegistry $modernPanelRegistry = null
    ) {}

    /**
     * @return list<array{id: int, key: string, type: string, active: int, order: int}>
     */
    public function getInstalledPluginData(): array
    {
        $plugins = $this->installationManager->getInstalledPluginData();

        usort($plugins, static fn (array $left, array $right): int => ($left['order'] <=> $right['order'])
            ?: ($left['id'] <=> $right['id']));

        return $plugins;
    }

    /**
     * @return list<array{id: int, key: string, name: string, description: string, type: int, active: int, order: int, config_link: ?string}>
     */
    public function getInstalledPluginAdminEntries(?int $type = null): array
    {
        $entries = [];

        foreach ($this->getInstalledPluginData() as $data) {
            if ($type !== null && $data['type'] !== PluginType::string($type)) {
                continue;
            }

            $modern = $this->modernPanelMetadataFor($data['key']);

            if ($modern !== null) {
                $entries[] = [
                    'id' => $data['id'],
                    'key' => $data['key'],
                    'name' => $modern->getName(),
                    'description' => $modern->getDescription(),
                    'type' => PluginType::PANEL,
                    'active' => $data['active'],
                    'order' => $data['order'],
                    'config_link' => null,
                ];

                continue;
            }

            $legacy = $this->pluginInstantiator->instantiate($data['key'], $data['id'], $data);
            $entries[] = [
                'id' => $legacy->id(),
                'key' => $legacy->key(),
                'name' => $legacy->name(),
                'description' => $legacy->description(),
                'type' => $legacy->type(),
                'active' => $data['active'],
                'order' => $data['order'],
                'config_link' => $legacy->getConfigLink(),
            ];
        }

        return $entries;
    }

    /**
     * @return list<array{key: string, name: string, description: string, type: int}>
     */
    public function getAvailablePluginAdminEntries(): array
    {
        $installed = $this->installationManager->getInstalledPluginKeys();
        $entries = [];

        foreach ($this->pluginDiscovery->findAvailablePluginKeys() as $key) {
            if (in_array($key, $installed, true)) {
                continue;
            }

            $modern = $this->modernPanelMetadataFor($key);

            if ($modern !== null) {
                $entries[] = [
                    'key' => $key,
                    'name' => $modern->getName(),
                    'description' => $modern->getDescription(),
                    'type' => PluginType::PANEL,
                ];

                continue;
            }

            $legacy = $this->pluginInstantiator->instantiate($key, PluginInstaller::ID);
            $entries[] = [
                'key' => $legacy->key(),
                'name' => $legacy->name(),
                'description' => $legacy->description(),
                'type' => $legacy->type(),
            ];
        }

        return $entries;
    }

    private function modernPanelMetadataFor(string $key): ?ModernPanelMetadataInterface
    {
        if ($this->modernPanelRegistry === null || ! $this->modernPanelRegistry->has($key)) {
            return null;
        }

        $panel = $this->modernPanelRegistry->get($key);

        return $panel instanceof ModernPanelMetadataInterface ? $panel : null;
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
     * @return list<Plugin>
     */
    public function getInstalledPluginsExceptInactive(?int $type = null): array
    {
        $plugins = [];

        foreach ($this->getInstalledPluginData() as $data) {
            if (($type !== null && $data['type'] !== PluginType::string($type)) || $data['active'] === Plugin::ACTIVE_NOT) {
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
     * @throws InvalidArgumentException if no plugin is installed with the given key
     * @throws LogicException if there is no authenticated account
     */
    public function getInstalledPluginByKey(string $key): Plugin
    {
        foreach ($this->getInstalledPluginData() as $data) {
            if ($data['key'] === $key) {
                return $this->pluginInstantiator->instantiate($key, $data['id'], $data);
            }
        }

        throw new InvalidArgumentException(sprintf('Plugin with key "%s" is not installed.', $key));
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
