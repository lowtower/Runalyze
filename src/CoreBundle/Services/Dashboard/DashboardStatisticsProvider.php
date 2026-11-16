<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use Plugin;
use PluginType;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\HttpFoundation\Request;

final readonly class DashboardStatisticsProvider
{
    public function __construct(
        private PluginSettingsProvider $pluginSettingsProvider
    ) {}

    public function getStatistics(Request $request): DashboardStatistics
    {
        $statistics = $this->pluginSettingsProvider->getInstalledPlugins(PluginType::STAT);
        $activeStatistics = array_values(array_filter(
            $statistics,
            static fn (Plugin $plugin): bool => $plugin->isActive()
        ));
        $variousStatistics = array_values(array_filter(
            $statistics,
            static fn (Plugin $plugin): bool => $plugin->isHidden()
        ));
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
            $plugin = $this->pluginSettingsProvider->getInstalledPluginById($request->query->getInt('pluginid'));

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
