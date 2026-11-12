<?php

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use PluginFactory;
use PluginType;
use Symfony\Component\HttpFoundation\Request;

final readonly class DashboardStatisticsProvider
{
    public function __construct(
        private PluginFactory $pluginFactory
    ) {}

    /**
     * @throws \\InvalidArgumentException when the requested plugin does not exist
     */
    public function getStatistics(Request $request): DashboardStatistics
    {
        $activeStatistics = $this->pluginFactory->activePlugins(PluginType::STAT);
        $navigation = '';

        foreach ($activeStatistics as $index => $key) {
            $plugin = $this->pluginFactory->newInstance($key);
            $navigation .= '<li' . ($index === 0 ? ' class="active"' : '') . '>' . $plugin->getLink() . '</li>';
        }

        $variousStatistics = $this->pluginFactory->variousPlugins();

        if ($variousStatistics !== []) {
            $navigation .= '<li class="with-submenu">';
            $navigation .= '<a href="#">' . __('Miscellaneous') . '</a>';
            $navigation .= '<ul class="submenu">';

            foreach ($variousStatistics as $key) {
                $plugin = $this->pluginFactory->newInstance($key);
                $navigation .= '<li>' . $plugin->getLink() . '</li>';
            }

            $navigation .= '</ul>';
            $navigation .= '</li>';
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
            $plugin = $this->pluginFactory->newInstanceFor($request->query->getInt('pluginid'));

            if (! $plugin->isInActive()) {
                return new DashboardStatistics($navigation, null, $plugin, $activeStatistics !== []);
            }
        }

        if ($activeStatistics === []) {
            return new DashboardStatistics($navigation, null, null, false);
        }

        return new DashboardStatistics(
            $navigation,
            null,
            $this->pluginFactory->newInstance($activeStatistics[0]),
            true
        );
    }
}
