<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Plugins\Statistics\Wettkampf\RaceStatisticsPresenter;
use Runalyze\Plugins\Statistics\Wettkampf\RaceStatisticsSettings;
use Runalyze\Plugins\Statistics\Wettkampf\RaceYearComparison;

final readonly class SharedAthleteStatisticsProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private RaceresultRepository $races,
        private ConfigurationManager $configurationManager,
        private RaceStatisticsSettings $settings
    ) {}

    public function getRaceYearComparison(Account $account): ?array
    {
        $repository = $this->doctrine->getRepository(Plugin::class);
        assert($repository instanceof PluginRepository);
        $installation = $repository->findForAccountAndKey($account, RaceStatisticsPresenter::KEY);
        if (! $installation instanceof \Runalyze\Bundle\CoreBundle\Entity\Plugin) {
            return null;
        }

        $general = $this->configurationManager->getList($account)->getGeneral();

        return RaceYearComparison::table(
            $this->races->findForStatistics($account, $general->getMainSport()),
            $this->settings->forInstallation($account, $installation)['distances'],
            $general->getDistanceUnitSystem()
        );
    }
}
