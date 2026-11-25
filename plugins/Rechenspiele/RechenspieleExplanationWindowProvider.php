<?php

declare(strict_types=1);

namespace RunalyzePluginsRechenspiele;

use DateTimeImmutable;
use DateTimeZone;
use RunalyzeBundleCoreBundleEntityAccount;
use RunalyzeBundleCoreBundleRepositoryTrainingRepository;
use RunalyzeBundleCoreBundleServicesConfigurationConfigurationManager;
use RunalyzeParameterApplicationTimezone as TimezoneEnum;
use RunalyzeSportsPerformanceModelTsbModel;
use RunalyzeSportsRunningMarathonShape;
use RunalyzeSportsRunningPrognosisVO2max as Vo2maxPrognosis;
use RunalyzeSportsRunningVO2maxEffectiveVO2max;
use RunalyzeSportsRunningVO2maxEffectiveVO2maxCorrector;
use SymfonyComponentClockClockInterface;

/**
 * Prepares all data for the Rechenspiele explanation window, account-scoped
 * and without legacy database access.
 */
final readonly class RechenspieleExplanationWindowProvider
{
    public function __construct(
        private RechenspieleTrimpDataProvider $trimpDataProvider,
        private TrainingRepository $trainingRepository,
        private ConfigurationManager $configurationManager,
        private ClockInterface $clock
    ) {}

    /**
     * @return array{
     *     trimp: array<string, bool|int|float|string>,
     *     vo2max: array<string, mixed>,
     *     basic_endurance: array<string, mixed>
     * }
     */
    public function getWindowData(Account $account): array
    {
        return [
            'trimp' => $this->getTrimpOverview($account),
            'vo2max' => $this->getEffectiveVo2maxOverview($account),
            'basic_endurance' => $this->getBasicEnduranceOverview($account),
        ];
    }

    /**
     * @return array{
     *     has_history: bool,
     *     atl: int,
     *     atl_absolute: string,
     *     atl_days: int,
     *     atl_max: int,
     *     ctl: int,
     *     ctl_absolute: string,
     *     ctl_days: int,
     *     ctl_max: int,
     *     tsb: string,
     *     tsb_equation: string
     * }
     */
    public function getTrimpOverview(Account $account): array
    {
        $configuration = $this->configurationManager->getList($account);
        $atlDays = (int) $configuration->get('trimp.ATL_DAYS');
        $ctlDays = (int) $configuration->get('trimp.CTL_DAYS');
        $model = new TsbModel($this->trimpDataProvider->getPerformanceModelData($account), $ctlDays, $atlDays);
        $model->calculate();

        $atl = $model->fatigueAt(0);
        $ctl = $model->fitnessAt(0);
        $tsb = $model->performanceAt(0);
        $maxAtl = max(1, (int) $configuration->get('data.MAX_ATL'));
        $maxCtl = max(1, (int) $configuration->get('data.MAX_CTL'));

        return [
            'has_history' => $atl != 0.0 || $ctl != 0.0,
            'atl' => (int) round(100 * $atl / $maxAtl),
            'atl_absolute' => number_format($atl, 2),
            'atl_days' => $atlDays,
            'atl_max' => $maxAtl,
            'ctl' => (int) round(100 * $ctl / $maxCtl),
            'ctl_absolute' => number_format($ctl, 2),
            'ctl_days' => $ctlDays,
            'ctl_max' => $maxCtl,
            'tsb' => number_format($tsb, 2),
            'tsb_equation' => number_format($ctl, 2) . ' - ' . number_format($atl, 2) . ' = ' . number_format($tsb, 2),
        ];
    }

    /**
     * @return array{
     *     current: string,
     *     days: int,
     *     unit: string,
     *     values: list<array{id: int, date: string, distance: string, vo2max: string, tooltip: string}>
     * }
     */
    public function getEffectiveVo2maxOverview(Account $account): array
    {
        $configuration = $this->configurationManager->getList($account);
        $vo2maxCategory = $configuration->getVO2max();
        $vo2max = $configuration->getCurrentVO2maxShape();
        $days = $vo2maxCategory->getNumberOfDaysToConsider();
        $effective = new EffectiveVO2max(
            0,
            new EffectiveVO2maxCorrector($configuration->getVO2maxCorrectionFactor())
        );
        $timezone = $this->timezoneFor($account);
        $fromTimestamp = $this->clock->now()->getTimestamp() - $days * 86400;

        $values = [];

        foreach (
            $this->trainingRepository->findEffectiveVo2maxHistoryFor(
                $account,
                $configuration->useVO2maxCorrectionForElevation(),
                $fromTimestamp
            ) as $row
        ) {
            $date = (new DateTimeImmutable('@' . $row['time']))->setTimezone($timezone)->format('d.m.Y');
            $distance = number_format($configuration->getUnitSystem()->getDistanceUnit()->fromBaseUnit($row['distance']), 2);
            $effective->setValue($row['vo2max']);

            $values[] = [
                'id' => $row['id'],
                'date' => $date,
                'distance' => $distance,
                'vo2max' => number_format($effective->value(), 2),
                'tooltip' => $date . ': ' . $distance,
            ];
        }

        return [
            'current' => number_format($vo2max, 2),
            'days' => $days,
            'unit' => $configuration->getUnitSystem()->getDistanceUnit()->getAppendix(),
            'values' => $values,
        ];
    }

    /**
     * @return array{
     *     unit: string,
     *     vo2max: string,
     *     has_vo2max: bool,
     *     percentage: int,
     *     week_days: int,
     *     week_weeks: int,
     *     week_target_km: string,
     *     week_percentage: int,
     *     week_avg_km: string,
     *     week_factor: string,
     *     longjog_days: int,
     *     longjog_weeks: int,
     *     longjog_target_km: string,
     *     longjog_percentage: int,
     *     longjog_points: string,
     *     longjog_factor: string,
     *     marathon_time: string,
     *     minimal_longjog_km: string,
     *     runs: list<array{id: int, date: string, distance: string, points: string}>,
     *     ignored_runs: int
     * }
     */
    public function getBasicEnduranceOverview(Account $account): array
    {
        $configuration = $this->configurationManager->getList($account);
        $basicEndurance = $configuration->getBasicEndurance();
        $vo2max = $configuration->getCurrentVO2maxShape();
        $usedVO2max = max($vo2max, (float) $basicEndurance::MINIMAL_EFFECTIVE_VO2MAX);

        $timezone = $this->timezoneFor($account);
        $today = $this->clock->now()->setTimezone($timezone);
        $startTimestamp = (int) $configuration->getData()->getStartTime();
        $accountAgeDays = 0;

        if ($startTimestamp > 0) {
            $firstDay = (new DateTimeImmutable('@' . $startTimestamp))->setTimezone($timezone)->setTime(0, 0);

            if ($firstDay <= $today) {
                $accountAgeDays = (int) $firstDay->diff($today->setTime(0, 0))->format('%r%a');
            }
        }

        $weekDays = $basicEndurance->getDaysToConsiderForWeeklyMileage($accountAgeDays);
        $longJogDays = $basicEndurance->getDaysToConsiderForLongJogs();
        $minimalLongJogKm = (float) $basicEndurance->getMinimalDistanceForLongJog();
        $targetWeekKm = $usedVO2max ** 1.135;
        $targetLongJogKm = max(0.1, (new MarathonShape($usedVO2max, $basicEndurance))->getTargetForLongJogEachWeek() - $minimalLongJogKm);

        $endTimestamp = $today->setTime(23, 59, 59)->getTimestamp();
        $distanceUnit = $configuration->getUnitSystem()->getDistanceUnit();
        $formatDistance = static fn (float $km): string => number_format($distanceUnit->fromBaseUnit($km), 2);

        $kilometers = 0.0;
        $longJogPoints = 0.0;
        $runs = [];
        $ignoredRuns = 0;

        foreach (
            $this->trainingRepository->findBasicEnduranceRunsFor(
                $account,
                $configuration->getGeneral()->getRunningSport(),
                $endTimestamp - max($weekDays, $longJogDays) * 86400,
                $endTimestamp + 1
            ) as $run
        ) {
            if ($run['time'] >= $endTimestamp - $weekDays * 86400) {
                $kilometers += $run['distance'];
            }

            if ($run['time'] < $endTimestamp - $longJogDays * 86400 || $run['distance'] <= $minimalLongJogKm) {
                continue;
            }

            $weight = 2 - 2 / $longJogDays * floor(($endTimestamp - $run['time']) / 86400);
            $points = $weight * ($run['distance'] - $minimalLongJogKm) ** 2 / $targetLongJogKm ** 2;
            $longJogPoints += $points;

            if ($points >= 0.2) {
                $runs[] = [
                    'id' => $run['id'],
                    'date' => (new DateTimeImmutable('@' . $run['time']))->setTimezone($timezone)->format('d.m.Y'),
                    'distance' => $formatDistance($run['distance']),
                    'points' => number_format(round($points, 1), 1),
                ];
            } else {
                ++$ignoredRuns;
            }
        }

        $weekKmPercentage = $kilometers * 7 / $weekDays / $targetWeekKm;
        $longJogPercentage = $longJogPoints * 7 / $longJogDays;
        $marathonSeconds = (new Vo2maxPrognosis($usedVO2max))->getSeconds(42.195);

        return [
            'unit' => $distanceUnit->getAppendix(),
            'vo2max' => number_format($vo2max, 2),
            'has_vo2max' => $vo2max != 0.0,
            'percentage' => (int) round(100 * (
                $weekKmPercentage * $basicEndurance->getPercentageForWeeklyMileage()
                + $longJogPercentage * $basicEndurance->getPercentageForLongJogs()
            )),
            'week_days' => $weekDays,
            'week_weeks' => (int) round($weekDays / 7),
            'week_target_km' => $formatDistance($targetWeekKm),
            'week_percentage' => (int) round(100 * $weekKmPercentage),
            'week_avg_km' => $formatDistance($kilometers / $weekDays * 7),
            'week_factor' => (string) round($basicEndurance->getPercentageForWeeklyMileage(), 2),
            'longjog_days' => $longJogDays,
            'longjog_weeks' => (int) round($longJogDays / 7),
            'longjog_target_km' => $formatDistance($targetLongJogKm + $minimalLongJogKm),
            'longjog_percentage' => (int) round(100 * $longJogPercentage),
            'longjog_points' => number_format(round($longJogPoints, 1), 1),
            'longjog_factor' => (string) round($basicEndurance->getPercentageForLongJogs(), 2),
            'marathon_time' => gmdate('H:i:s', (int) $marathonSeconds),
            'minimal_longjog_km' => $formatDistance($minimalLongJogKm),
            'runs' => $runs,
            'ignored_runs' => $ignoredRuns,
        ];
    }

    private function timezoneFor(Account $account): DateTimeZone
    {
        return new DateTimeZone(TimezoneEnum::getFullNameByEnum($account->getTimezone()));
    }
}
