<?php

namespace Runalyze\Calculation;

use DB;
use Runalyze\Configuration;
use Runalyze\Util\LocalTime;
use Runalyze\Util\Time;

/**
 * This class can calculated a value for the basic endurance.
 * A value of 100 represents a fully sufficient training for an optimal marathon.
 * The requirements for a sufficient basic endurance are calculated from the given VO2max.
 *
 * This class can use the settings from configuration or with own settings.
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation
 */
class BasicEndurance
{
    /**
     * This value refers to the constant configuration value.
     */
    private static int|bool $CONST_VALUE = false;

    /**
     * @var float [ml/kg/min]
     */
    public const float MINIMAL_EFFECTIVE_VO2MAX = 25.0;

    /**
     * @var float [ml/kg/min]
     */
    protected float $EffectiveVO2max = 0.0;

    protected float $MIN_KM_FOR_LONGJOG = 13;

    protected int $DAYS_FOR_WEEK_KM = 182;

    protected int $DAYS_FOR_WEEK_KM_MIN = 70;

    protected int $DAYS_FOR_LONGJOGS = 70;

    protected float $PERCENTAGE_WEEK_KM = 0.67;

    protected float $PERCENTAGE_LONGJOGS = 0.33;

    public function readSettingsFromConfiguration(): void
    {
        $this->EffectiveVO2max = Configuration::Data()->vo2max();

        $BasicEndurance = Configuration::BasicEndurance();
        $this->MIN_KM_FOR_LONGJOG = $BasicEndurance->minKmForLongjog();
        $this->DAYS_FOR_WEEK_KM = $BasicEndurance->daysForWeekKm();
        $this->DAYS_FOR_WEEK_KM_MIN = $BasicEndurance->daysForWeekKmMin();
        $this->DAYS_FOR_LONGJOGS = $BasicEndurance->daysForLongjogs();
        $this->PERCENTAGE_WEEK_KM = $BasicEndurance->percentageWeekKm();
        $this->PERCENTAGE_LONGJOGS = $BasicEndurance->percentageLongjogs();
    }

    /**
     * @param float $EffectiveVO2max [ml/kg/min]
     */
    public function setEffectiveVO2max(float $EffectiveVO2max): void
    {
        $this->EffectiveVO2max = $EffectiveVO2max;
    }

    /**
     * @return float [ml/kg/min]
     */
    public function getUsedEffectiveVO2max(): float
    {
        return max($this->EffectiveVO2max, self::MINIMAL_EFFECTIVE_VO2MAX);
    }

    /**
     * Only trainings above a given distance are treated as "longjogs".
     *
     * @param float $km minimal distance for longjog
     */
    public function setMinimalDistanceForLongjogs(float $km): void
    {
        $this->MIN_KM_FOR_LONGJOG = $km;
    }

    public function getMinimalDistanceForLongjogs(): float
    {
        return $this->MIN_KM_FOR_LONGJOG;
    }

    /**
     * @see setMinimalDaysToRecognizeForWeekKilometer()
     */
    public function setDaysToRecognizeForWeekKilometer(int $days): void
    {
        $this->DAYS_FOR_WEEK_KM = $days;
    }

    public function getDaysToRecognizeForWeekKilometer(): int
    {
        return $this->DAYS_FOR_WEEK_KM;
    }

    /**
     * For new users it's senseless to look one year back, but it's senseless to look only one week back.
     * Therefore a minimal number of days can be specified.
     */
    public function setMinimalDaysToRecognizeForWeekKilometer(int $days): void
    {
        $this->DAYS_FOR_WEEK_KM_MIN = $days;
    }

    public function getMinimalDaysToRecognizeForWeekKilometer(): int
    {
        return $this->DAYS_FOR_WEEK_KM_MIN;
    }

    public function setDaysToRecognizeForLongjogs(int $days): void
    {
        $this->DAYS_FOR_LONGJOGS = $days;
    }

    public function getDaysToRecognizeForLongjogs(): int
    {
        return $this->DAYS_FOR_LONGJOGS;
    }

    /**
     * Percentage has to be between 0 and 1. A value higher than 1 will be treated as 1.
     */
    public function setPercentageForWeekKilometer(float $percentage): void
    {
        $this->PERCENTAGE_WEEK_KM = min(1, abs($percentage));
        $this->PERCENTAGE_LONGJOGS = 1 - $this->PERCENTAGE_WEEK_KM;
    }

    public function getPercentageForWeekKilometer(): float
    {
        return $this->PERCENTAGE_WEEK_KM;
    }

    /**
     * Percentage has to be between 0 and 1. A value higher than 1 will be treated as 1.
     */
    public function setPercentageForLongjogs(float $percentage): void
    {
        $this->PERCENTAGE_LONGJOGS = min(1, abs($percentage));
        $this->PERCENTAGE_WEEK_KM = 1 - $this->PERCENTAGE_LONGJOGS;
    }

    public function getPercentageForLongjogs(): float
    {
        return $this->PERCENTAGE_LONGJOGS;
    }

    /**
     * @param array $data [optional] array('km' => km sum in time range, 'sum' => sum of weighted points for long jogs)
     * @return array array('weekkm-result', 'longjog-result', 'weekkm-percentage', 'longjog-percentage', 'percentage')
     */
    public function asArray(int $timestamp = 0, array $data = []): array
    {
        if ($timestamp === 0) {
            $timestamp = time();
        }

        // If you change the algorithm, remember to change *info* in 'RunalyzePluginPanel_Rechenspiele'.
        $DataSum = $data !== [] ? $data : DB::getInstance()->query($this->getQuery($timestamp))->fetch();
        $Result = [];
        $Result['weekkm-result'] = $DataSum['km'] ?? 0;
        $Result['longjog-result'] = $DataSum['sum'] ?? 0;

        $Result['weekkm-percentage'] = $Result['weekkm-result'] * 7 / $this->getDaysForWeekKm() / $this->getTargetWeekKm();
        $Result['longjog-percentage'] = $Result['longjog-result'] * 7 / $this->DAYS_FOR_LONGJOGS;
        $Result['percentage'] = round(100 * ($Result['weekkm-percentage'] * $this->PERCENTAGE_WEEK_KM + $Result['longjog-percentage'] * $this->PERCENTAGE_LONGJOGS));

        return $Result;
    }

    /**
     * The calculated value can be higher than 100.
     * To get a limited percentage, @see valueInPercent()
     */
    public function value(int $timestamp = 0): int
    {
        $Result = $this->asArray($timestamp);

        return $Result['percentage'];
    }

    public function valueInPercent(int $timestamp = 0): string
    {
        return min(100, max(0, $this->value($timestamp))) . ' %';
    }

    public function getQuery(int $timestamp = 0, bool $onlyLongjogs = false): string
    {
        if ($timestamp === 0) {
            $timestamp = LocalTime::fromServerTime(time())->setTime(23, 59, 59)->getTimestamp();
        } else {
            $timestamp = LocalTime::fromServerTime($timestamp)->setTime(23, 59, 59)->getTimestamp();
        }

        $StartTimeForLongjogs = $timestamp - $this->DAYS_FOR_LONGJOGS * DAY_IN_S;
        $StartTimeForWeekKm = $timestamp - $this->getDaysForWeekKm() * DAY_IN_S;

        if ($onlyLongjogs) {
            return '
				SELECT
					`id`,
					`time`,
					`distance`,
					IF (
						`distance` > ' . $this->MIN_KM_FOR_LONGJOG . ' AND time >= ' . $StartTimeForLongjogs . ',
						(
							(2 - (2/' . $this->DAYS_FOR_LONGJOGS . ') * FLOOR( (' . $timestamp . ' - `time`) / ' . DAY_IN_S . ' ) )
							* POW((`distance`-' . $this->MIN_KM_FOR_LONGJOG . ')/' . $this->getTargetLongjogKmPerWeek() . ',2)
						),
						0
					) as `points`
				FROM ' . PREFIX . 'training
				WHERE `accountid`=' . \SessionAccountHandler::getId() . ' AND `time` BETWEEN ' . $StartTimeForLongjogs . ' AND ' . $timestamp . ' AND `sportid`=' . Configuration::General()->runningSport() . ' AND distance>' . $this->MIN_KM_FOR_LONGJOG;
        }

        return '
			SELECT
				SUM(
					IF (time >= ' . $StartTimeForWeekKm . ', `distance`, 0)
				) as `km`,
				SUM(
					IF (
						`distance` > ' . $this->MIN_KM_FOR_LONGJOG . ' AND time >= ' . $StartTimeForLongjogs . ',
						(
							(2 - (2/' . $this->DAYS_FOR_LONGJOGS . ') * FLOOR( (' . $timestamp . ' - `time`) / ' . DAY_IN_S . ' ) )
							* POW((`distance`-' . $this->MIN_KM_FOR_LONGJOG . ')/' . $this->getTargetLongjogKmPerWeek() . ',2)
						),
						0
					)
				) as `sum`
			FROM ' . PREFIX . 'training
			WHERE `accountid`=' . \SessionAccountHandler::getId() . ' AND `time` BETWEEN ' . min($StartTimeForLongjogs, $StartTimeForWeekKm) . ' AND ' . $timestamp . ' AND `sportid`=' . Configuration::General()->runningSport() . '
			GROUP BY accountid
			LIMIT 1';
    }

    /**
     * @param array $activityData array(array('time' => ..., 'distance' => ...), array(...), ...)
     * @return array<string, int|float> array('km' => km sum in time range, 'sum' => sum of weighted points for long jogs)
     */
    public function dataFor(array $activityData, int $timestamp): array
    {
        $km = 0;
        $points = 0;

        $startTimeForLongjogs = $timestamp - $this->DAYS_FOR_LONGJOGS * DAY_IN_S;
        $startTimeForWeekKm = $timestamp - $this->getDaysForWeekKm() * DAY_IN_S;
        $targetLongJogSquared = $this->getTargetLongjogKmPerWeek() ** 2;

        foreach ($activityData as $data) {
            if ($data['time'] > $startTimeForWeekKm) {
                $km += $data['distance'];
            }

            if ($data['time'] > $startTimeForLongjogs && $data['distance'] > $this->MIN_KM_FOR_LONGJOG) {
                $points += (2 - 2 / $this->DAYS_FOR_LONGJOGS * floor(($timestamp - $data['time']) / DAY_IN_S)) * ($data['distance'] - $this->MIN_KM_FOR_LONGJOG) ** 2 / $targetLongJogSquared;
            }
        }

        return [
            'km' => $km,
            'sum' => $points,
        ];
    }

    public function getDaysForWeekKm(): float
    {
        $diff = Time::diffInDays(START_TIME);

        if ($diff > $this->DAYS_FOR_WEEK_KM) {
            return $this->DAYS_FOR_WEEK_KM;
        }

        if ($diff < $this->DAYS_FOR_WEEK_KM_MIN) {
            return $this->DAYS_FOR_WEEK_KM_MIN;
        }

        return $diff;
    }

    public function getTargetWeekKm(): float
    {
        return max($this->EffectiveVO2max, self::MINIMAL_EFFECTIVE_VO2MAX) ** 1.135;
    }

    /**
     * PAY ATTENTION: $MIN_KM_FOR_LONGJOG is already subtracted!
     */
    public function getTargetLongjogKmPerWeek(): float
    {
        return log(max($this->EffectiveVO2max, self::MINIMAL_EFFECTIVE_VO2MAX) / 4) * 12 - $this->MIN_KM_FOR_LONGJOG;
    }

    public function getRealTargetLongjogKmPerWeek(): float
    {
        return $this->getTargetLongjogKmPerWeek() + $this->MIN_KM_FOR_LONGJOG;
    }

    public static function getConst(): int|bool
    {
        if (self::$CONST_VALUE === false) {
            if (Configuration::Data()->basicEndurance() !== 0) {
                self::$CONST_VALUE = Configuration::Data()->basicEndurance();
            } else {
                self::recalculateValue();
            }
        }

        return self::$CONST_VALUE;
    }

    public static function recalculateValue(): void
    {
        $Object = new self();
        $Object->readSettingsFromConfiguration();

        $BASIC_ENDURANCE = $Object->value();

        Configuration::Data()->updateBasicEndurance($BASIC_ENDURANCE);

        self::$CONST_VALUE = $BASIC_ENDURANCE;
    }
}
