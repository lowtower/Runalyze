<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use InvalidArgumentException;
use LogicException;
use Plugin;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\HttpFoundation\Request;

final readonly class DashboardStatisticsProvider
{
    public function __construct(
        private PluginSettingsProvider $pluginSettingsProvider,
        private ?ModernStatisticsRegistry $modernStatisticsRegistry = null
    ) {}

    /**
     * @throws InvalidArgumentException if the requested plugin does not exist
     * @throws LogicException if there is no authenticated account
     */
    public function getStatistics(Request $request): DashboardStatistics
    {
        $installed = array_column($this->pluginSettingsProvider->getInstalledPluginData(), null, 'id');
        $plugins = [];
        $navigation = [];
        $miscellaneous = [];
        $firstActive = null;

        foreach ($installed as $id => $installation) {
            if ($installation['type'] !== PluginInstallation::TYPE_STAT
                || ! in_array($installation['active'], [PluginInstallation::STATE_ACTIVE, PluginInstallation::STATE_HIDDEN], true)
            ) {
                continue;
            }

            $modern = $this->modernStatisticsRegistry?->find($installation['key']);
            if ($modern instanceof ModernStatisticsInterface) {
                $plugin = $id;
                $label = $modern->getName();
            } else {
                $plugin = $this->pluginSettingsProvider->instantiateLegacyPlugin($installation);
                $label = $plugin->name();
            }

            $plugins[$id] = $plugin;
            $link = [
                'label' => $label,
                'href' => Plugin::$DISPLAY_URL . '/' . $id,
            ];

            if ($installation['active'] === PluginInstallation::STATE_HIDDEN) {
                $miscellaneous[] = $link;

                continue;
            }

            $navigation[] = $link + ['active' => $firstActive === null, 'children' => []];
            $firstActive ??= $plugin;
        }

        if ($miscellaneous !== []) {
            $navigation[] = [
                'label' => __('Miscellaneous'),
                'href' => '#',
                'active' => false,
                'children' => $miscellaneous,
            ];
        }

        if ($request->query->has('id')) {
            return new DashboardStatistics($navigation, $request->query->getInt('id'), null, $firstActive !== null);
        }

        if ($request->query->has('pluginid')) {
            $id = $request->query->getInt('pluginid');
            $installation = $installed[$id] ?? throw new InvalidArgumentException(sprintf('No plugin found for id "%d".', $id));

            if ($installation['active'] !== PluginInstallation::STATE_INACTIVE) {
                $plugin = $plugins[$id] ?? $this->pluginSettingsProvider->instantiateLegacyPlugin($installation);

                return new DashboardStatistics($navigation, null, $plugin, $firstActive !== null);
            }
        }

        return new DashboardStatistics($navigation, null, $firstActive, $firstActive !== null);
    }
}
