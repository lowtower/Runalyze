<?php

declare(strict_types=1);

namespace Runalyze\\Plugins\\Sports;

use Runalyze\\Bundle\\CoreBundle\\Entity\\Account;
use Runalyze\\Bundle\\CoreBundle\\Entity\\Plugin;
use Runalyze\\Bundle\\CoreBundle\\Model\\Sport\\SportStatistics;
use Runalyze\\Bundle\\CoreBundle\\Plugin\\Panel\\ModernPanelInterface;
use Runalyze\\Bundle\\CoreBundle\\Repository\\SportRepository;
use Runalyze\\Util\\LocalTime;
use Symfony\\Component\\Clock\\ClockInterface;

final readonly class SportsPanelPresenter implements ModernPanelInterface
{
    public function __construct(
        private SportRepository $sportRepository,
        private ClockInterface $clock
    ) {}

    public function getKey(): string
    {
        return 'RunalyzePluginPanel_Sports';
    }

    public function getTemplate(): string
    {
        return '@Sports/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    /**
     * @return array{
     *     week_statistics: SportStatistics,
     *     month_statistics: SportStatistics,
     *     year_statistics: SportStatistics,
     *     total_statistics: SportStatistics
     * }
     */
    public function present(Account $account, Plugin $installation): array
    {
        $today = new LocalTime($this->clock->now()->getTimestamp())->setTime(0, 0, 0);

        /** @var SportStatistics $weekStatistics */
        $weekStatistics = $this->sportRepository->getSportStatisticsSince($today->weekstart(), $account);
        /** @var SportStatistics $monthStatistics */
        $monthStatistics = $this->sportRepository->getSportStatisticsSince($today->monthStart(), $account);
        /** @var SportStatistics $yearStatistics */
        $yearStatistics = $this->sportRepository->getSportStatisticsSince($today->yearStart(), $account);
        /** @var SportStatistics $totalStatistics */
        $totalStatistics = $this->sportRepository->getSportStatisticsSince(null, $account);

        return [
            'week_statistics' => $weekStatistics,
            'month_statistics' => $monthStatistics,
            'year_statistics' => $yearStatistics,
            'total_statistics' => $totalStatistics,
        ];
    }
}
