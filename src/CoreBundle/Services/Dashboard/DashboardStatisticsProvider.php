<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use InvalidArgumentException;
use LogicException;
use Plugin;
use PluginType;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\HttpFoundation\Request;

final readonly class DashboardStatisticsProvider
{
    public function __construct(
        private PluginSettingsProvider $pluginSettingsProvider
    ) {}

    /**
     * @throws InvalidArgumentException if the requested plugin does not exist
     * @throws LogicException if there is no authenticated account
     */
    public function getStatistics(Request $request): DashboardStatistics
    {
        $activeStatistics = $this->pluginSettingsProvider->getInstalledPlugins(PluginType::STAT, Plugin::ACTIVE);
        $variousStatistics = $this->pluginSettingsProvider->getInstalledPlugins(PluginType::STAT, Plugin::ACTIVE_VARIOUS);
        $navigation = [];

        foreach ($activeStatistics as $index => $plugin) {
            $navigation[] = [
                'label' => $plugin->name(),
                'href' => Plugin::$DISPLAY_URL . '/' . $plugin->id(),
                'active' => $index === 0,
                'children' => [],
            ];
        }

        if ($variousStatistics !== []) {
            $children = [];

            foreach ($variousStatistics as $plugin) {
                $children[] = [
                    'label' => $plugin->name(),
                    'href' => Plugin::$DISPLAY_URL . '/' . $plugin->id(),
                ];
            }

            $navigation[] = [
                'label' => __('Miscellaneous'),
                'href' => '#',
                'active' => false,
                'children' => $children,
            ];
        }

        if ($request->query->has('id')) {
            return new DashboardStatistics(
                $navigation,
                $request->query->getInt('id'),
                null,
                $activeStatistics !== []
            );
        }

        if ($request->query->has('pluginid')) {
            $pluginId = $request->query->getInt('pluginid');
            $plugin = null;

            foreach ([$activeStatistics, $variousStatistics] as $installedPlugins) {
                foreach ($installedPlugins as $installedPlugin) {
                    if ($installedPlugin->id() === $pluginId) {
                        $plugin = $installedPlugin;

                        break 2;
                    }
                }
            }

            $plugin ??= $this->pluginSettingsProvider->getInstalledPluginById($pluginId);

            if (! $plugin->isInActive()) {
                return new DashboardStatistics($navigation, null, $plugin, $activeStatistics !== []);
            }
        }

        if ($activeStatistics === []) {
            return new DashboardStatistics($navigation, null, null, false);
        }

        return new DashboardStatistics($navigation, null, $activeStatistics[0], true);
    }
}
