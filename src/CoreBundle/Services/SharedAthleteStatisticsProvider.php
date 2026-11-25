<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;

final readonly class SharedAthleteStatisticsProvider
{
    private const string RACE_PLUGIN_KEY = 'RunalyzePluginStat_Wettkampf';

    public function __construct(
        private ManagerRegistry $doctrine,
        private LegacyPluginInstantiator $pluginInstantiator
    ) {}

    /**
     * @throws InvalidArgumentException if the installed plugin implementation is invalid
     */
    public function getTabForComparisonOfYears(Account $account): string
    {
        foreach ($this->getPluginRepository()->findDataForAccount($account) as $pluginData) {
            if ($pluginData['key'] !== self::RACE_PLUGIN_KEY) {
                continue;
            }

            $plugin = $this->pluginInstantiator->instantiate(
                $pluginData['key'],
                $pluginData['id'],
                $pluginData
            );

            if (! $plugin instanceof \RunalyzePluginStat_Wettkampf) {
                throw new InvalidArgumentException('The installed race plugin has an invalid implementation.');
            }

            $content = $plugin->getYearComparisonTable();

            if ($content !== false && $content !== '') {
                return $content;
            }

            break;
        }

        return '<em>' . __('No data available.') . '</em>';
    }

    private function getPluginRepository(): PluginRepository
    {
        $repository = $this->doctrine->getRepository(Plugin::class);
        assert($repository instanceof PluginRepository);

        return $repository;
    }
}
