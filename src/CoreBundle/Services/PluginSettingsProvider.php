<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Ajax;
use Icon;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelRegistry;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;

final readonly class PluginSettingsProvider
{
    public function __construct(
        private PluginInstallationManager $installationManager,
        private PluginDiscovery $pluginDiscovery,
        private PanelRegistry $panelRegistry,
        private StatisticsRegistry $statisticsRegistry
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
                ? $this->statisticsRegistry->find($data['key'])
                : null;

            if ($statistic instanceof StatisticsInterface) {
                $entries[] = [
                    'id' => $data['id'],
                    'key' => $data['key'],
                    'name' => $statistic->getName(),
                    'description' => $statistic->getDescription(),
                    'type' => Plugin::TYPE_STAT,
                    'active' => $data['active'],
                    'order' => $data['order'],
                    'config_link' => $statistic instanceof StatisticsConfigurationInterface
                        ? $this->configurationLink($data['id'])
                        : null,
                ];

                continue;
            }

            $panel = $data['type'] === Plugin::TYPE_PANEL
                ? $this->panelRegistry->find($data['key'])
                : null;

            if ($panel instanceof PanelInterface) {
                $entries[] = [
                    'id' => $data['id'],
                    'key' => $data['key'],
                    'name' => $panel->getName(),
                    'description' => $panel->getDescription(),
                    'type' => Plugin::TYPE_PANEL,
                    'active' => $data['active'],
                    'order' => $data['order'],
                    'config_link' => $panel instanceof PanelConfigurationInterface
                        ? $this->configurationLink($data['id'])
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

            $statistic = $this->statisticsRegistry->find($key);

            if ($statistic instanceof StatisticsInterface) {
                $entries[] = [
                    'key' => $key,
                    'name' => $statistic->getName(),
                    'description' => $statistic->getDescription(),
                    'type' => Plugin::TYPE_STAT,
                ];

                continue;
            }

            $panel = $this->panelRegistry->find($key);

            if ($panel instanceof PanelInterface) {
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

    private function configurationLink(int $id): string
    {
        return Ajax::window('<a href="call/call.Plugin.config.php?id=' . $id . '">' . Icon::$CONF . '</a>', 'small');
    }
}
