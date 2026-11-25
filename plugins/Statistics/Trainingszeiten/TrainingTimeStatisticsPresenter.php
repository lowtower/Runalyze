<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Trainingszeiten;

use DateTimeImmutable;
use Plot;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsInstallation;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;
use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Util\Time;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @phpstan-import-type NightActivity from TrainingTimeDistribution
 */
final readonly class TrainingTimeStatisticsPresenter implements ModernStatisticsInterface, ModernStatisticsConfigurationInterface
{
    public const string KEY = 'RunalyzePluginStat_Trainingszeiten';

    private const string SHOW_NIGHTS = 'show_extreme_times';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private TrainingRepository $trainingRepository,
        private TrainingTimeDistribution $distribution,
        private ConfigurationManager $configurationManager,
        private DoctrinePluginConfigurationStorage $configurationStorage
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Training times');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Plot all your activity times by daytime/weekday and list your nightly activities.');
    }

    public function getTemplate(): string
    {
        return '@Trainingszeiten/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Trainingszeiten/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        return [
            'showNights' => $this->showNights($account, $installation),
            'label' => __('Show nightly activities'),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        StatisticsInstallation::requireId($account, $installation, self::KEY);
        $this->configurationStorage->saveValue($installation, self::SHOW_NIGHTS, isset($data[self::SHOW_NIGHTS]) ? '1' : '0');
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $showNights = $this->showNights($account, $installation);
        $filter = $this->filterFactory->fromQuery($account, $query);
        $filter['years'][-1] = __('Total');
        $sport = $filter['sport'] > 0 ? $filter['sport'] : null;
        $sports = $filter['sports'];
        unset($sports[-1]);
        if ($sport !== null) {
            $sports = [
                $sport => $sports[$sport],
            ];
        }

        $yearLabel = $filter['years'][$filter['year']] ?? (string) $filter['year'];
        $data = $this->distribution->summarize(
            $sports,
            $this->trainingRepository->findTrainingTimesFor($account, $sport, $filter['from'], $filter['until']),
            $showNights,
            $sport !== null
        );
        $configuration = $this->configurationManager->getList($account);
        $units = $configuration->getGeneral()->getDistanceUnitSystem();
        $mondayStart = (int) $configuration->get('general.WEEK_START') === 1;
        $decimals = (int) $configuration->get('activity-view.TRAINING_DECIMALS');
        $ticks = [];
        for ($day = 1; $day <= 7; ++$day) {
            $ticks[] = [$day - 1, Time::weekday((string) $day, true)];
        }

        return [
            'pluginId' => $installation->getId(),
            'filter' => $filter,
            'yearLabel' => $yearLabel,
            'title' => $this->getName() . ': ' . ($sport === null ? '' : $sports[$sport] . ', ') . $yearLabel,
            'daytime' => $this->plot('daytime', $data['daytime'], $sports),
            'weekday' => $this->plot('weekday', $data['weekday'], $sports, $ticks),
            'nightTitle' => __('Nightly activities'),
            'nightDescription' => __('2 a.m. is considered as <em>most extreme</em> time for a training. ')
                . __('The 20 trainings being nearest to that time are listed.'),
            'nights' => array_map(
                fn (array $row): array => $this->nightRow($row, $sports[$row['sportId']], $units, $decimals, $mondayStart),
                $data['nights']
            ),
        ];
    }

    /**
     * @param array<int, non-empty-array<int, float>> $series
     * @param array<int, string> $sports
     * @param list<array{int, string}> $ticks
     * @return array{html: string, script: string}
     */
    private function plot(string $id, array $series, array $sports, array $ticks = []): array
    {
        $plot = new Plot($id, 350, 190);
        $maximum = 0.0;
        foreach ($series as $sportId => $hours) {
            $plot->Data[] = [
                'label' => htmlspecialchars($sports[$sportId], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'data' => $hours,
            ];
            $maximum += max($hours);
        }

        $plot->setLegendAsTable();
        $plot->setMarginForGrid(5);
        if ($ticks !== []) {
            $plot->setXLabels($ticks);
        }

        $plot->addYAxis(1, 'left');
        $plot->addYUnit(1, 'h');
        $plot->setYTicks(1, 1, false);
        $plot->setYLimits(1, 0, $maximum);
        $plot->showBars(true);
        $plot->stacked();
        if ($maximum === 0.0) {
            $plot->raiseError(__('No data available.'));
        }

        return [
            'html' => $plot->getDiv(),
            'script' => $plot->getJavaScript(true),
        ];
    }

    /**
     * @param NightActivity $row
     * @return array<string, mixed>
     */
    private function nightRow(array $row, string $sportName, DistanceUnitSystem $units, int $decimals, bool $mondayStart): array
    {
        $date = new DateTimeImmutable('@' . $row['time']);
        $daysSinceWeekStart = ((int) $date->format('w') + ($mondayStart ? 6 : 0)) % 7;
        $weekStart = $date->setTime(0, 0)->modify('-' . $daysSinceWeekStart . ' days');
        $distance = new Distance($row['distance'], $units);
        $value = match (true) {
            $row['distance'] <= 0 => Duration::format($row['duration']),
            $row['isTrack'] => $distance->stringMeter(),
            default => $distance->string(true, $decimals),
        };

        return [
            'id' => $row['id'],
            'time' => $date->format('H:i'),
            'date' => $date->format('d.m.Y'),
            'sportName' => $sportName,
            'sportIcon' => $row['sportIcon'],
            'duration' => $row['duration'],
            'value' => $value,
            'weekStart' => $weekStart->getTimestamp(),
            'weekEnd' => $weekStart->modify('+6 days')->setTime(23, 59, 50)->getTimestamp(),
        ];
    }

    private function showNights(Account $account, Plugin $installation): bool
    {
        $id = StatisticsInstallation::requireId($account, $installation, self::KEY);
        $configuration = $this->configurationStorage->loadForPlugins([$id]);

        return ($configuration[$id][self::SHOW_NIGHTS] ?? '1') === '1';
    }
}
