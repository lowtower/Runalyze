<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Hoehenmeter;

use DateTimeImmutable;
use DateTimeZone;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Elevation;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInterface;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class ElevationStatisticsPresenter implements StatisticsInterface
{
    public const string KEY = 'RunalyzePluginStat_Hoehenmeter';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Elevation');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Your steepest activities and an overview of your cumulated elevation.');
    }

    public function getTemplate(): string
    {
        return '@Hoehenmeter/statistics.html.twig';
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $configuration = $this->configurationManager->getList($account);
        $general = $configuration->getGeneral();
        $units = $general->getDistanceUnitSystem();
        $mondayStart = (int) $configuration->get('general.WEEK_START') === 1;
        $filter = $this->filterFactory->fromQuery($account, $query, defaultSport: $general->getMainSport(), defaultYear: -1);
        $sport = $filter['sport'] > 0 ? $filter['sport'] : null;
        $yearLabel = $filter['years'][$filter['year']] ?? (string) $filter['year'];
        $showMonthlyTotals = $filter['year'] === -1;
        $rankings = $this->trainingRepository->findElevationRankingsFor($account, $sport, $filter['from'], $filter['until']);

        return [
            'pluginId' => $installation->getId(),
            'filter' => $filter,
            'title' => $this->getName() . ': ' . ($sport === null ? '' : $filter['sports'][$sport] . ', ') . $yearLabel,
            'yearLabel' => $yearLabel,
            'showMonthlyTotals' => $showMonthlyTotals,
            'monthlyTotals' => $showMonthlyTotals ? $this->monthlyTotals($account, $sport, $units) : [],
            'rankings' => array_map(
                fn (array $rows): array => array_map(fn (array $row): array => $this->rankingRow($row, $units, $mondayStart), $rows),
                $rankings
            ),
            'rankingLabels' => [
                'elevation' => __('Most elevation'),
                'grade' => __('Steepest routes'),
                'cs' => __('Highest Climb Score'),
            ],
            'climbScoreLabel' => __('Climb Score'),
            'emptyMessage' => __('No routes found.'),
            'unlabeled' => __('unlabeled'),
        ];
    }

    /**
     * @return array<int, array{months: array<int, array{from: string, until: string, value: string}>, total: string}>
     */
    private function monthlyTotals(Account $account, ?int $sport, DistanceUnitSystem $units): array
    {
        $totals = [];
        foreach ($this->trainingRepository->findMonthlyElevationFor($account, $sport) as $year => $elevations) {
            $months = [];
            foreach ($elevations as $month => $meters) {
                $firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), new DateTimeZone('UTC'));
                $months[$month] = [
                    'from' => $firstDay->format('d.m.Y'),
                    'until' => $firstDay->modify('last day of this month')->format('d.m.Y'),
                    'value' => new Elevation($meters, $units)->string(),
                ];
            }

            $totals[$year] = [
                'months' => $months,
                'total' => new Elevation(array_sum($elevations), $units)->string(),
            ];
        }

        return $totals;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function rankingRow(array $row, DistanceUnitSystem $units, bool $mondayStart): array
    {
        // Stored timestamps already represent the local calendar, with no timezone offset.
        $date = new DateTimeImmutable('@' . $row['time']);
        $daysSinceWeekStart = ((int) $date->format('w') + ($mondayStart ? 6 : 0)) % 7;
        $weekStart = $date->setTime(0, 0)->modify('-' . $daysSinceWeekStart . ' days');

        return [
            'id' => (int) $row['id'],
            'date' => $date->format('d.m.Y'),
            'weekStart' => $weekStart->getTimestamp(),
            'weekEnd' => $weekStart->modify('+6 days')->setTime(23, 59, 50)->getTimestamp(),
            'sportName' => $row['sportName'] ?? '',
            'sportIcon' => $row['sportIcon'] ?? '',
            'duration' => (float) ($row['duration'] ?? 0),
            'route' => $row['route'] ?? '',
            'title' => $row['title'] ?? '',
            'grade' => round((float) ($row['grade'] ?? 0), 2),
            'elevation' => $row['elevation'] === null ? null : new Elevation((float) $row['elevation'], $units)->string(),
            'distance' => $row['distance'] === null ? null : new Distance((float) $row['distance'], $units)->string(),
            'climbScore' => $row['climbScore'] === null ? null : number_format((float) $row['climbScore'], 1, '.', ''),
        ];
    }
}
