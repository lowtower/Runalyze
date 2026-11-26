<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;
use Symfony\Component\Clock\ClockInterface;

/**
 * Coordinates the account-scoped shape plot history and calculation services.
 */
final readonly class RechenspieleShapePlotDataProvider
{
    public function __construct(
        private ConfigurationManager $configurationManager,
        private ClockInterface $clock,
        private RechenspieleShapePlotDateRange $dateRange,
        private RechenspieleShapeDataProvider $dailyDataProvider,
        private RechenspieleShapeSeriesCalculator $seriesCalculator,
        private RechenspieleMarathonShapeSeriesCalculator $marathonShapeCalculator,
        private RechenspieleShapePlotSeriesAssembler $seriesAssembler,
        private RechenspieleShapePlotTimestampMapper $timestampMapper
    ) {}

    /**
     * @return array{
     *     atl: array<string, float>,
     *     ctl: array<string, float>,
     *     tsb: array<string, float>,
     *     trimp: array<string, float>,
     *     vo2max: array<string, float>,
     *     vo2max_day: array<string, float>,
     *     marathon_shape: array<string, int>,
     *     max_atl: int,
     *     max_ctl: int,
     *     recomputed_maxima: array{atl: int, ctl: int}|null,
     *     max_trimp: float,
     *     show_in_percent: bool,
     *     calculate_maxima: bool,
     *     limited_year: ?int,
     *     mark_future: bool,
     *     today_timestamp: int,
     *     last_timestamp: string
     * }|null
     *
     * @throws InvalidArgumentException if the plot selection or configured windows are invalid
     */
    public function getPlotData(Account $account, string $range, string $model): ?array
    {
        if (! in_array($model, RechenspielePlotSelection::availableModels(), true)) {
            throw new InvalidArgumentException('Unknown shape plot performance model.');
        }

        $configuration = $this->configurationManager->getList($account);
        $startTimestamp = $configuration->getData()->getStartTime();

        if ($startTimestamp <= 0) {
            return null;
        }

        $timezone = new DateTimeZone(TimezoneEnum::getFullNameByEnum($account->getTimezone()));
        $firstActivity = new DateTimeImmutable('@' . $startTimestamp)->setTimezone($timezone);
        $today = $this->clock->now()->setTimezone($timezone);
        $basicEndurance = $configuration->getBasicEndurance();

        $ctlDays = (int) $configuration->get('trimp.CTL_DAYS');
        $atlDays = (int) $configuration->get('trimp.ATL_DAYS');
        $vo2maxDays = $configuration->getVO2max()->getNumberOfDaysToConsider();
        $accountAgeDays = (int) $firstActivity->setTime(0, 0)->diff($today->setTime(0, 0))->format('%r%a');
        $weekKmDays = $basicEndurance->getDaysToConsiderForWeeklyMileage($accountAgeDays);

        $period = $this->dateRange->resolve(
            $range,
            $firstActivity,
            $today,
            $atlDays,
            $ctlDays,
            $vo2maxDays,
            $weekKmDays,
            $basicEndurance->getDaysToConsiderForLongJogs()
        );

        $daily = $this->dailyDataProvider->getDailyData(
            $account,
            $configuration->getGeneral()->getRunningSport(),
            $period['start_day'],
            $period['end_day'],
            $period['warmup_days'],
            (float) $basicEndurance->getMinimalDistanceForLongJog(),
            $configuration->useVO2maxCorrectionForElevation()
        );

        $calculated = $this->seriesCalculator->calculate(
            $daily['trimp'],
            $daily['vo2max_weighted'],
            $daily['vo2max_sum_time'],
            $model,
            $ctlDays,
            $atlDays,
            $vo2maxDays,
            $period['lowest_index'],
            $period['highest_index'],
            (int) $configuration->get('data.MAX_ATL'),
            (int) $configuration->get('data.MAX_CTL'),
            $period['calculate_maxima'],
            $configuration->get('trimp.TRIMP_MODEL_IN_PERCENT') === 'true',
            $configuration->getVO2maxCorrectionFactor()
        );

        $marathonShape = $this->marathonShapeCalculator->calculate(
            $daily['distance'],
            $daily['longjogs'],
            $calculated['vo2max'],
            $basicEndurance,
            $period['lowest_index'],
            $period['highest_index'],
            $accountAgeDays
        );

        $series = $this->seriesAssembler->assemble(
            $calculated,
            $marathonShape,
            $period['start_day'],
            $period['warmup_days']
        );

        return [
            ...$series,
            'recomputed_maxima' => $calculated['recomputed_maxima'],
            'calculate_maxima' => $period['calculate_maxima'],
            'limited_year' => $period['limited_year'],
            'mark_future' => $period['mark_future'],
            'today_timestamp' => $today->getTimestamp(),
            'last_timestamp' => $this->timestampMapper->timestampForIndex(
                $period['start_day'],
                $period['warmup_days'],
                $period['highest_index']
            ),
        ];
    }
}
