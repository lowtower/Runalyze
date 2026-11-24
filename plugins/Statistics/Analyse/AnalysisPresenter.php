<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\Analyse;

use DateTimeImmutable;
use InvalidArgumentException;
use Runalyze\Activity\Pace;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\ModernStatisticsInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Statistics\StatisticsFilterFactory;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Repository\TypeRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\DoctrinePluginConfigurationStorage;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Parameter\Application\HeartRateUnit;
use Runalyze\Util\Time;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @phpstan-type Setting array{label: string, unit: string, default: int|bool, value: int|bool}
 * @phpstan-type PaceLimits array{slowest: int, fastest: int, step: int}
 * @phpstan-type Filter array{sport: int, year: int, sports: array<int, string>, years: array<int, string>, from: ?int, until: ?int}
 */
final readonly class AnalysisPresenter implements ModernStatisticsInterface, ModernStatisticsConfigurationInterface
{
    public const string KEY = 'RunalyzePluginStat_Analyse';

    public function __construct(
        private StatisticsFilterFactory $filterFactory,
        private SportRepository $sportRepository,
        private TypeRepository $typeRepository,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager,
        private DoctrinePluginConfigurationStorage $configurationStorage
    ) {}

    public function getKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return __('Analysis');
    }

    public function getDescription(?Account $account = null): string
    {
        return __('Analyze your training (only running) by means of pace, heart rate and different types.');
    }

    public function getTemplate(): string
    {
        return '@Analyse/statistics.html.twig';
    }

    public function getConfigurationTemplate(): string
    {
        return '@Analyse/configuration.html.twig';
    }

    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        $id = $this->installationId($account, $installation);

        return [
            'settings' => $this->settings($this->configurationStorage->loadForPlugins([$id])[$id] ?? []),
        ];
    }

    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->installationId($account, $installation);
        $values = [];
        foreach ($this->settings([]) as $key => $setting) {
            if (is_bool($setting['default'])) {
                $values[$key] = isset($data[$key]) ? '1' : '0';
            } elseif (is_scalar($data[$key] ?? null) && is_numeric($data[$key])) {
                $values[$key] = (string) (int) $data[$key];
            }
        }

        $this->configurationStorage->saveValues($installation, $values);
    }

    public function present(Account $account, Plugin $installation, InputBag $query): array
    {
        $id = $this->installationId($account, $installation);
        $configuration = $this->configurationManager->getList($account);
        $general = $configuration->getGeneral();
        $filter = $this->filterFactory->fromQuery($account, $query, $general->getMainSport());
        $filter['years'][-1] = __('Year on year');
        unset($filter['sports'][-1]);
        if ($filter['sport'] === -1 && $filter['sports'] !== []) {
            $filter['sport'] = isset($filter['sports'][$general->getMainSport()])
                ? $general->getMainSport()
                : array_key_first($filter['sports']);
        }

        $sport = $filter['sport'] > 0 ? $this->sportRepository->findThisOrAny($filter['sport'], $account) : null;
        $yearLabel = $filter['years'][$filter['year']] ?? (string) $filter['year'];
        $metrics = [
            '' => __('in percent'),
        ];
        if ($sport?->getDistances()) {
            $metrics['km'] = __('by distance');
        }

        $metrics['s'] = __('by time');
        $metric = $query->getString('dat');
        if (! isset($metrics[$metric])) {
            $metric = '';
        }

        $periods = $this->periods($filter['year'], $filter['years'], $filter['from']);
        $settings = $this->settings($this->configurationStorage->loadForPlugins([$id])[$id] ?? []);
        $tables = $sport instanceof Sport ? $this->tables($account, $sport, $filter, $periods, $settings, $configuration) : [];

        return [
            'pluginId' => $id,
            'filter' => $filter,
            'title' => $sport instanceof Sport ? $sport->getName() . ' ' . $yearLabel : $this->getName(),
            'yearLabel' => $yearLabel,
            'metrics' => $metrics,
            'metric' => $metric,
            'hasDistances' => $sport?->getDistances() ?? false,
            'periods' => $periods,
            'tables' => $tables,
            'note' => __('The values consider only the average data fields of your activities.'),
        ];
    }

    /**
     * @param array<string, string> $stored
     * @return array<string, Setting>
     */
    private function settings(array $stored): array
    {
        $settings = [
            'use_type' => [__('Analyze types'), '', true],
            'use_pace' => [__('Analyze pace zones'), '', true],
            'use_pulse' => [__('Analyze heart rate zones'), '', true],
            'lowest_pulsegroup' => [__('Lowest heart rate zone'), __('[%HFmax]'), 65],
            'pulsegroup_step' => [__('Heart rate zone: Increment'), __('[%HFmax]'), 5],
            'lowest_pacegroup' => [__('Lowest pace zone'), __('[s/km]'), 450],
            'highest_pacegroup' => [__('Highest pace zone'), __('[s/km]'), 240],
            'pacegroup_step' => [__('Pace zone: Increment'), __('[s/km]'), 15],
        ];

        $result = [];
        foreach ($settings as $key => [$label, $unit, $default]) {
            $value = $stored[$key] ?? null;
            $result[$key] = [
                'label' => $label,
                'unit' => $unit,
                'default' => $default,
                'value' => $value === null ? $default : (is_bool($default) ? $value === '1' : (int) $value),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, string> $years
     * @return array<int, string>
     */
    private function periods(int $year, array $years, ?int $from): array
    {
        if ($year === -1) {
            return array_filter(array_reverse($years, true), static fn (int $year): bool => $year >= 1000, ARRAY_FILTER_USE_KEY);
        }

        assert($from !== null);
        // Both rolling views display a 12-column calendar beginning at the first month in the range.
        $month = new DateTimeImmutable('@' . ($from - (int) ($year === 6 || $year === 12)));
        $periods = [];
        for ($index = 0; $index < 12; ++$index) {
            $periods[(int) $month->format('Ym')] = Time::month($month->format('n'), true);
            $month = $month->modify('+1 month');
        }

        return $periods;
    }

    /**
     * @param Filter $filter
     * @param array<int, string> $periods
     * @param array<string, Setting> $settings
     * @return list<array{name: string, labels: array<int, array{text: string, title?: string}>, data: AnalysisTable}>
     */
    private function tables(
        Account $account,
        Sport $sport,
        array $filter,
        array $periods,
        array $settings,
        RunalyzeConfigurationList $configuration
    ): array {
        $sportId = $filter['sport'];
        $types = $settings['use_type']['value'] ? new AnalysisTable() : null;
        $pace = $settings['use_pace']['value'] && $sport->getDistances() ? new AnalysisTable() : null;
        $pulse = $settings['use_pulse']['value'] ? new AnalysisTable() : null;

        if (! $types instanceof AnalysisTable && ! $pace instanceof AnalysisTable && ! $pulse instanceof AnalysisTable) {
            return [];
        }

        $typeLabels = $types instanceof AnalysisTable ? $this->typeLabels($account, $sport) : [];
        $paceLimits = $pace instanceof AnalysisTable ? $this->paceLimits($account, $filter, $configuration->getGeneral()->getRunningSport(), $settings) : null;
        $heartRateUnit = (string) $configuration->get('general.HEART_RATE_UNIT');
        $maxHeartRate = $configuration->getMaximalHeartRate();
        $baseHeartRate = $heartRateUnit === HeartRateUnit::HRRESERVE ? $configuration->getRestingHeartRate() : 0;
        $heartRateSpan = $maxHeartRate - $baseHeartRate;
        $lowestPulse = min(100, max(0, (int) $settings['lowest_pulsegroup']['value']));
        $pulseStep = min(100, max(1, (int) $settings['pulsegroup_step']['value']));
        $highestPulse = $lowestPulse + (int) ceil((100 - $lowestPulse) / $pulseStep) * $pulseStep;
        $pulseOffset = $lowestPulse % $pulseStep;

        $periodFormat = $filter['year'] === -1 ? 'Y' : 'Ym';
        foreach ($this->trainingRepository->iterateActivityAnalysisSamples($account, $sportId, $filter['from'], $filter['until']) as $sample) {
            $period = (int) gmdate($periodFormat, $sample['time']);
            if (! isset($periods[$period])) {
                continue;
            }

            $distance = $sample['distance'] ?? 0.0;
            $duration = $sample['duration'] ?? 0.0;
            $typeId = $sample['typeId'] ?? 0;
            $types?->add(isset($typeLabels[$typeId]) ? $typeId : 0, $period, $distance, $duration);
            if ($pace instanceof AnalysisTable && $paceLimits !== null && $distance > 0 && $duration > 0) {
                $offset = $paceLimits['slowest'] % $paceLimits['step'];
                $secondsPerKm = $duration / $distance;
                $group = floor(($secondsPerKm - $offset) / $paceLimits['step']) * $paceLimits['step'] + $offset;
                $pace->add((int) max($paceLimits['fastest'], min($paceLimits['slowest'], $group)), $period, $distance, $duration);
            }

            if ($pulse instanceof AnalysisTable && $sample['heartRate'] > 0 && $heartRateSpan > 0) {
                $percentage = 100 * ($sample['heartRate'] - $pulseOffset - $baseHeartRate) / $heartRateSpan;
                $group = (int) ceil($percentage / $pulseStep) * $pulseStep + $pulseOffset;
                $pulse->add(max($lowestPulse, min($highestPulse, $group)), $period, $distance, $duration);
            }
        }

        $tables = [];
        if ($types instanceof AnalysisTable) {
            if (isset($types->rows[0])) {
                $typeLabels = [
                    0 => [
                        'text' => __('without'),
                    ],
                ] + $typeLabels;
            }

            $tables[] = [
                'name' => __('Training types'),
                'labels' => $typeLabels,
                'data' => $types,
            ];
        }

        if ($pace instanceof AnalysisTable) {
            $tables[] = [
                'name' => __('Pace Zones') . '*',
                'labels' => $paceLimits === null ? [] : $this->paceLabels($sport, $paceLimits, array_keys($pace->rows)),
                'data' => $pace,
            ];
        }

        if ($pulse instanceof AnalysisTable) {
            $tables[] = [
                'name' => __('Heart Rate Zones') . '*',
                'labels' => $this->pulseLabels($lowestPulse, $highestPulse, $pulseStep, $maxHeartRate, $heartRateUnit),
                'data' => $pulse,
            ];
        }

        return $tables;
    }

    /**
     * @return array<int, array{text: string, title: string}>
     */
    private function typeLabels(Account $account, Sport $sport): array
    {
        $types = $this->typeRepository->findAllFor($account, $sport);
        usort($types, static fn (Type $a, Type $b): int => $a->getHrAvg() <=> $b->getHrAvg());

        $labels = [];
        foreach ($types as $type) {
            $labels[(int) $type->getId()] = [
                'text' => $type->getAbbr() !== '' ? $type->getAbbr() : $type->getName(),
                'title' => $type->getName(),
            ];
        }

        return $labels;
    }

    /**
     * @param Filter $filter
     * @param array<string, Setting> $settings
     * @return PaceLimits|null
     */
    private function paceLimits(Account $account, array $filter, int $runningSport, array $settings): ?array
    {
        if ($filter['sport'] !== $runningSport) {
            $limits = $this->trainingRepository->findActivityAnalysisPaceLimits($account, $filter['sport'], $filter['from'], $filter['until']);

            return $limits === null ? null : $limits + [
                'step' => max(1, (int) round(($limits['slowest'] - $limits['fastest']) / 10)),
            ];
        }

        $slowest = max(1, (int) $settings['lowest_pacegroup']['value']);

        return [
            'slowest' => $slowest,
            'fastest' => min($slowest, max(1, (int) $settings['highest_pacegroup']['value'])),
            'step' => max(1, (int) $settings['pacegroup_step']['value']),
        ];
    }

    /**
     * @param PaceLimits $limits
     * @param list<int> $populated
     * @return array<int, array{text: string}>
     */
    private function paceLabels(Sport $sport, array $limits, array $populated): array
    {
        if ($populated === []) {
            return [];
        }

        ['slowest' => $slowest, 'fastest' => $fastest, 'step' => $step] = $limits;
        $unit = new LegacyUnitConverter()->getLegacyPaceUnit($sport->getSpeed(), true);
        assert(is_int($unit));
        $pace = new Pace(0, 1, $unit);

        $span = $slowest - $fastest;
        $groups = [$slowest, $fastest];
        if (intdiv($span, $step) < 500) {
            for ($group = $slowest - $step; $group > $fastest; $group -= $step) {
                $groups[] = $group;
            }
        } else {
            // Very wide saved ranges show the populated zones and their outer limits.
            $groups = array_unique([...$groups, ...$populated]);
        }

        rsort($groups, SORT_NUMERIC);

        $fastestThreshold = $span === 0 ? $slowest : $slowest - intdiv($span - 1, $step) * $step;
        $labels = [];
        foreach ($groups as $group) {
            $text = match ($group) {
                $fastest => __('faster than') . ' ' . $pace->setTime($fastestThreshold)->valueWithAppendix(),
                $slowest => __('up to') . ' ' . $pace->setTime($group)->valueWithAppendix(),
                default => $pace->setTime($group + $step)->value() . ' ' . __('to') . ' ' . $pace->setTime($group)->valueWithAppendix(),
            };
            $labels[$group] = [
                'text' => html_entity_decode($text, ENT_QUOTES, 'UTF-8'),
            ];
        }

        return $labels;
    }

    /**
     * @return array<int, array{text: string}>
     */
    private function pulseLabels(int $lowest, int $highest, int $step, int $maxHeartRate, string $unit): array
    {
        $labels = [];
        for ($group = $lowest; $group <= $highest; $group += $step) {
            $from = $group === $lowest ? 0 : $group - $step;
            $to = min($group, 100);
            if ($unit === HeartRateUnit::BPM) {
                $from = (int) ceil($maxHeartRate * $from / 100);
                $to = floor($maxHeartRate * $to / 100) . ' bpm';
            } else {
                $to .= ' %';
            }

            $labels[$group] = [
                'text' => $from . ' ' . __('to') . ' ' . $to,
            ];
        }

        return $labels;
    }

    private function installationId(Account $account, Plugin $installation): int
    {
        $id = $installation->getId();
        if ($id === null || $account->getId() === null || $installation->getAccount()->getId() !== $account->getId()
            || $installation->getKey() !== self::KEY || ! $installation->isStatistic()
        ) {
            throw new InvalidArgumentException('The analysis installation is not available for this account.');
        }

        return $id;
    }
}
