<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Rekorde;

use DateTimeImmutable;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Util\Time;
use Symfony\Component\HttpFoundation\InputBag;

final readonly class RecordStatisticsPresenter implements ModernStatisticsInterface
{
    public const string KEY = 'RunalyzePluginStat_Rekorde';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private SportRepository $sportRepository,
        private ConfigurationManager $configurationManager
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Records');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Faster, longer, better: Your records from your activities.');
    }

    public function getTemplate(): string
    {
        return '@Rekorde/statistics.html.twig';
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $period = $this->filterFactory->periodFromQuery($account, $query);
        $yearLabel = $period['years'][$period['year']] ?? (string) $period['year'];
        $configuration = $this->configurationManager->getList($account);
        $general = $configuration->getGeneral();
        $units = $general->getDistanceUnitSystem();
        $mondayStart = (int) $configuration->get('general.WEEK_START') === 1;

        $activityRecords = [
            'fastest' => [],
            'longest' => [],
        ];
        $sports = $this->sportRepository->findBy([
            'account' => $account,
        ], [
            'id' => 'ASC',
        ]);
        $paceUnits = new LegacyUnitConverter();
        foreach ($sports as $sport) {
            $sportId = $sport->getId();
            assert($sportId !== null);
            $records = $this->trainingRepository->findActivityRecordsFor($account, $sportId, $period['from'], $period['until']);
            $paceUnit = $paceUnits->getLegacyPaceUnit($sport->getSpeed(), true);

            foreach ($records as $type => $rows) {
                if ($rows !== []) {
                    $activityRecords[$type][] = [
                        'sport' => $sport,
                        'records' => array_map(
                            fn (array $row): array => $this->activityRecord($row, $units, $type === 'fastest' ? $paceUnit : null),
                            $rows
                        ),
                    ];
                }
            }
        }

        $distanceRecords = [];
        foreach ($this->trainingRepository->findDistanceRecordsFor($account, $general->getRunningSport(), $period['from'], $period['until'], $mondayStart) as $type => $rows) {
            $distanceRecords[$type] = array_map(
                fn (array $row): array => $this->distanceRecord($row, $type, $units, $mondayStart),
                $rows
            );
        }

        return [
            'pluginId' => $installation->getId(),
            'period' => $period,
            'title' => $this->getName() . ': ' . $yearLabel,
            'yearLabel' => $yearLabel,
            'activityRecords' => $activityRecords,
            'distanceRecords' => $distanceRecords,
            'recordTitles' => [
                'fastest' => __('Fastest activities'),
                'longest' => __('Longest activities'),
                'distance' => __('Most kilometers'),
            ],
            'periodLabels' => [
                'year' => __('per year'),
                'month' => __('per month'),
                'week' => __('per week'),
            ],
            'emptyMessage' => __('No data available'),
        ];
    }

    /**
     * @param array{id: int, time: int, duration: ?float, distance: ?float} $row
     * @return array{id: int, date: string, value: string}
     */
    private function activityRecord(array $row, DistanceUnitSystem $units, ?int $paceUnit): array
    {
        $distance = $row['distance'] ?? 0.0;
        $duration = $row['duration'] ?? 0.0;
        $value = match (true) {
            $paceUnit !== null => new Pace($duration, $distance, $paceUnit)->valueWithAppendix(),
            $distance !== 0.0 => new Distance($distance, $units)->string(),
            default => Duration::format($duration),
        };

        return [
            'id' => $row['id'],
            'date' => gmdate('d.m.Y', $row['time']),
            'value' => $value,
        ];
    }

    /**
     * @param array{time: int, distance: int|float|string} $row
     * @return array{label: string, start: int, end: int, value: string}
     */
    private function distanceRecord(array $row, string $type, DistanceUnitSystem $units, bool $mondayStart): array
    {
        $date = new DateTimeImmutable('@' . $row['time'])->setTime(0, 0);
        $year = (int) $date->format('Y');
        $daysSinceWeekStart = ((int) $date->format('w') + ($mondayStart ? 6 : 0)) % 7;
        $start = match ($type) {
            'year' => $date->setDate($year, 1, 1),
            'month' => $date->modify('first day of this month'),
            default => $date->modify('-' . $daysSinceWeekStart . ' days'),
        };
        $end = match ($type) {
            'year' => $date->setDate($year, 12, 31)->setTime(23, 59, 59),
            'month' => $date->modify('last day of this month')->setTime(23, 59, 59),
            default => $start->modify('+6 days')->setTime(23, 59, 50),
        };
        $week = $mondayStart ? $date : $date->modify('+1 day');
        $label = match ($type) {
            'year' => (string) $year,
            'month' => Time::month($date->format('n')) . ' ' . $year,
            default => __('Week') . ' ' . (int) $week->format('W') . ' ' . $week->format('o'),
        };

        return [
            'label' => $label,
            'start' => $start->getTimestamp(),
            'end' => $end->getTimestamp(),
            'value' => new Distance((float) $row['distance'], $units)->string(),
        ];
    }
}
