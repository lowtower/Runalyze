<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services\Dashboard;

use InvalidArgumentException;
use LogicException;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsRegistry;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Symfony\Component\HttpFoundation\Request;

final readonly class DashboardStatisticsProvider
{
    public function __construct(
        private PluginSettingsProvider $pluginSettingsProvider,
        private StatisticsRegistry $statisticsRegistry
    ) {}

    /**
     * @throws InvalidArgumentException if the requested plugin does not exist
     * @throws LogicException if there is no authenticated account
     */
    public function getStatistics(Request $request): DashboardStatistics
    {
        $installed = array_column($this->pluginSettingsProvider->getInstalledPluginData(), null, 'id');
        $navigation = [];
        $miscellaneous = [];
        $firstActive = null;

        foreach ($installed as $id => $installation) {
            if ($installation['type'] !== PluginInstallation::TYPE_STAT
                || ! in_array($installation['active'], [PluginInstallation::STATE_ACTIVE, PluginInstallation::STATE_HIDDEN], true)
            ) {
                continue;
            }

            $statistic = $this->statisticsRegistry->find($installation['key']);
            if (! $statistic instanceof StatisticsInterface) {
                continue;
            }

            $link = [
                'label' => $statistic->getName(),
                'href' => 'my/plugin/' . $id,
            ];

            if ($installation['active'] === PluginInstallation::STATE_HIDDEN) {
                $miscellaneous[] = $link;

                continue;
            }

            $navigation[] = $link + [
                'active' => $firstActive === null,
                'children' => [],
            ];
            $firstActive ??= $id;
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
                return new DashboardStatistics($navigation, null, $id, $firstActive !== null);
            }
        }

        return new DashboardStatistics($navigation, null, $firstActive, $firstActive !== null);
    }
}
