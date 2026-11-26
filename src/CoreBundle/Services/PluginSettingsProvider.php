<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Ajax;
use Icon;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;

final readonly class PluginSettingsProvider
{
    public function __construct(
        private PluginInstallationManager $installationManager,
        private PluginDiscovery $pluginDiscovery,
        private ModernPanelRegistry $modernPanelRegistry,
        private ModernStatisticsRegistry $modernStatisticsRegistry
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
     * @return list<array{id: int, key: string, name: string, description: string, type: string, active: int, order: int, config_link: ?string}>
     */
    public function getInstalledPluginAdminEntries(?string $type = null): array
    {
        $entries = [];

        foreach ($this->getInstalledPluginData() as $data) {
            if ($type !== null && $data['type'] !== $type) {
                continue;
            }

            $statistic = $data['type'] === Plugin::TYPE_STAT
                ? $this->modernStatisticsRegistry->find($data['key'])
                : null;

            if ($statistic instanceof ModernStatisticsInterface) {
                $entries[] = [
                    'id' => $data['id'],
                    'key' => $data['key'],
                    'name' => $statistic->getName(),
                    'description' => $statistic->getDescription(),
                    'type' => Plugin::TYPE_STAT,
                    'active' => $data['active'],
                    'order' => $data['order'],
                    'config_link' => null,
                ];

                continue;
            }

            $panel = $data['type'] === Plugin::TYPE_PANEL
                ? $this->modernPanelRegistry->find($data['key'])
                : null;

            if ($panel instanceof ModernPanelInterface) {
                $entries[] = [
                    'id' => $data['id'],
                    'key' => $data['key'],
                    'name' => $panel->getName(),
                    'description' => $panel->getDescription(),
                    'type' => Plugin::TYPE_PANEL,
                    'active' => $data['active'],
                    'order' => $data['order'],
                    'config_link' => $panel instanceof ModernPanelConfigurationInterface
                        ? Ajax::window(
                            '<a href="call/call.Plugin.config.php?id=' . $data['id'] . '">' . Icon::$CONF . '</a>',
                            'small'
                        )
                        : null,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return list<array{key: string, name: string, description: string, type: string}>
     */
    public function getAvailablePluginAdminEntries(): array
    {
        $installed = $this->installationManager->getInstalledPluginKeys();
        $entries = [];

        foreach ($this->pluginDiscovery->findAvailablePluginKeys() as $key) {
            if (in_array($key, $installed, true)) {
                continue;
            }

            $statistic = $this->modernStatisticsRegistry->find($key);

            if ($statistic instanceof ModernStatisticsInterface) {
                $entries[] = [
                    'key' => $key,
                    'name' => $statistic->getName(),
                    'description' => $statistic->getDescription(),
                    'type' => Plugin::TYPE_STAT,
                ];

                continue;
            }

            $panel = $this->modernPanelRegistry->find($key);

            if ($panel instanceof ModernPanelInterface) {
                $entries[] = [
                    'key' => $key,
                    'name' => $panel->getName(),
                    'description' => $panel->getDescription(),
                    'type' => Plugin::TYPE_PANEL,
                ];
            }
        }

        return $entries;
    }
}
