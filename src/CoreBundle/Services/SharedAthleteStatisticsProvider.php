<?php

declare(strict_types=1);

namespace Runalyze\\Bundle\\CoreBundle\\Services;

use Doctrine\\Persistence\\ManagerRegistry;
use LogicException;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Plugin;
use Runalyze\\Bundle\\CoreBundle\\Repository\\PluginRepository;
use Runalyze\\Configuration;
use Runalyze\\Dataset\\Configuration as DatasetConfiguration;

final readonly class SharedAthleteStatisticsProvider
{
    private const string RACE_PLUGIN_KEY = 'RunalyzePluginStat_Wettkampf';

    public function __construct(
        private ManagerRegistry $doctrine,
        private LegacyPluginInstantiator $pluginInstantiator
    ) {}

    public function getYearComparisonTable(Account $account): SummaryTableAllYears
    {
        $accountId = $account->getId();

        if ($accountId === null) {
            throw new LogicException('Shared athlete statistics require a persisted account.');
        }

        require_once FRONTEND_PATH . '../plugin/RunalyzePluginStat_Statistiken/class.SummaryTable.php';
        require_once FRONTEND_PATH . '../plugin/RunalyzePluginStat_Statistiken/class.SummaryTableAllYears.php';

        return new SummaryTableAllYears(
            new DatasetConfiguration(DB::getInstance(), $accountId),
            Configuration::General()->runningSport(),
            -1
        );
    }

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

            assert($plugin instanceof \\RunalyzePluginStat_Wettkampf);

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
