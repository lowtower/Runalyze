<?php

/**
 * This file contains class::Entity
 * @package Runalyze\Model\Trackdata
 */

namespace Runalyze\Model\Trackdata;

use Runalyze\Calculation\Activity\PaceCalculator;
use Runalyze\Calculation\Activity\VerticalRatioCalculator;
use Runalyze\Calculation\StrideLength;
use Runalyze\Model;

/**
 * Trackdata entity.
 *
 * !!!! #TSC if you add a new array which can contain only 0, exclude it in the
 * #checkArraySizes like TEMPERATURE or PERFORMANCE_CONDITION !!!!!!!!!!!!!!!!!
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model\Trackdata
 */
class Entity extends Model\Entity implements Model\Loopable, Model\Common\WithNullableArraysInterface
{
    use Model\Common\WithNullableArraysTrait;

    /**
     * Key: activity id
     */
    public const string ACTIVITYID = 'activityid';

    /**
     * Key: time
     */
    public const string TIME = 'time';

    /**
     * Key: distance
     */
    public const string DISTANCE = 'distance';

    /**
     * Key: pace - must be calculated (not in db!)
     */
    public const string PACE = 'pace';

    /**
     * Key: heart rate
     */
    public const string HEARTRATE = 'heartrate';

    /**
     * Key: cadence
     */
    public const string CADENCE = 'cadence';

    /**
     * Key: stridelength - must be calculated (not in db!)
     */
    public const string STRIDE_LENGTH = 'stridelength';

    /**
     * Key: power
     */
    public const string POWER = 'power';

    /**
     * Key: temperature
     */
    public const string TEMPERATURE = 'temperature';

    /**
     * Key: ground contact time
     */
    public const string GROUNDCONTACT = 'groundcontact';

    /**
     * Key: vertical oscillation
     */
    public const string VERTICAL_OSCILLATION = 'vertical_oscillation';

    /**
     * Key: vertical ratio - must be calculated (not in db!)
     */
    public const string VERTICAL_RATIO = 'vertical_ratio';

    /**
     * Key: ground contact time balance
     */
    public const string GROUNDCONTACT_BALANCE = 'groundcontact_balance';

    /**
     * Key: smo2 (0)
     */
    public const string SMO2_0 = 'smo2_0';

    /**
     * Key: smo2 (1)
     */
    public const string SMO2_1 = 'smo2_1';

    /**
     * Key: thb (0)
     */
    public const string THB_0 = 'thb_0';

    /**
     * Key: thb (1)
     */
    public const string THB_1 = 'thb_1';

    /**
     * Key: impact GS left
     */
    public const string IMPACT_GS_LEFT = 'impact_gs_left';

    /**
     * Key: impact GS right
     */
    public const string IMPACT_GS_RIGHT = 'impact_gs_right';

    /**
     * Key: braking GS left
     */
    public const string BRAKING_GS_LEFT = 'braking_gs_left';

    /**
     * Key: braking GS right
     */
    public const string BRAKING_GS_RIGHT = 'braking_gs_right';

    /**
     * Key: footstrike type left
     */
    public const string FOOTSTRIKE_TYPE_LEFT = 'footstrike_type_left';

    /**
     * Key: footstrike type right
     */
    public const string FOOTSTRIKE_TYPE_RIGHT = 'footstrike_type_right';

    /**
     * Key: pronation excursion left
     */
    public const string PRONATION_EXCURSION_LEFT = 'pronation_excursion_left';

    /**
     * Key: pronation excursion right
     */
    public const string PRONATION_EXCURSION_RIGHT = 'pronation_excursion_right';

    /**
     * Key: performance condition
     */
    public const string PERFORMANCE_CONDITION = 'performance_condition';

    /**
     * Key: respiration rate
     */
    public const string RESPIRATION_RATE = 'respiration_rate';

    /**
     * Key: pauses
     */
    public const string PAUSES = 'pauses';

    /**
     * Pauses
     */
    protected \Runalyze\Model\Trackdata\Pauses $Pauses;

    protected bool $TimeHasBeenRemoved = false;

    /**
     * Flag: ensure arrays to be equally sized
     */
    protected bool $checkArraySizes = true;

    protected bool $hasTheoreticalPace = false;

    /**
     * Clone object
     */
    public function __clone()
    {
        $this->cloneInternalObjects();
    }

    /**
     * Construct
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->calculatePaceArray();
        $this->calculateStrideLengthArray();
        $this->calculateVerticalRatioArray();
        $this->readPauses();
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkArraySizes(): void
    {
        foreach ($this->properties() as $key) {
            if ($this->isArray($key)) {
                try {
                    // #TSC temp & perf-cond can have only 0 values in the array, so exclude it
                    if ($key != self::TEMPERATURE && $key != self::PERFORMANCE_CONDITION
                        && ! empty($this->Data[$key]) && max($this->Data[$key]) == 0) {
                        $this->Data[$key] = [];
                    }

                    // TODO: Move all these fixes to a new script to correct defect activities
                    if ($key == self::TIME && ! empty($this->Data[$key]) && min($this->Data[$key]) < 0) {
                        $this->TimeHasBeenRemoved = true;
                        $this->Data[$key] = [];
                    }

                    $count = count($this->Data[$key]);

                    if ($key == self::DISTANCE && $count > 0) {
                        $this->fixDistanceArray();
                    }

                    if (($key == self::HEARTRATE || $key == self::CADENCE) && $this->numberOfPoints > 0 && $count > 0) {
                        if ($count === 1 + $this->numberOfPoints) {
                            $this->Data[$key] = array_slice($this->Data[$key], 1);
                        } elseif ($count === $this->numberOfPoints - 1) {
                            array_unshift($this->Data[$key], $this->Data[$key][0]);
                        }
                    } else {
                        $this->checkArraySize($count);
                    }
                } catch (\RuntimeException $E) {
                    throw new \RuntimeException($E->getMessage() . ' (for ' . $key . ')', $E->getCode(), $E);
                }
            }
        }
    }

    protected function fixDistanceArray()
    {
        foreach ($this->Data[self::DISTANCE] as $i => $dist) {
            if ($i > 0 && $dist == 0) {
                $this->Data[self::DISTANCE][$i] = $this->Data[self::DISTANCE][$i - 1];
            }
        }
    }

    protected function readPauses()
    {
        $this->Pauses = new Pauses($this->Data[self::PAUSES]);
    }

    public function synchronize(): void
    {
        if ($this->Pauses->isEmpty()) {
            $this->Data[self::PAUSES] = null;
        } else {
            $this->Data[self::PAUSES] = $this->Pauses->asString();
        }
    }

    /**
     * @return array<int, string>
     */
    public static function allDatabaseProperties(): array
    {
        return [
            self::ACTIVITYID,
            self::TIME,
            self::DISTANCE,
            self::HEARTRATE,
            self::CADENCE,
            self::POWER,
            self::TEMPERATURE,
            self::GROUNDCONTACT,
            self::VERTICAL_OSCILLATION,
            self::GROUNDCONTACT_BALANCE,
            self::SMO2_0,
            self::SMO2_1,
            self::THB_0,
            self::THB_1,
            self::IMPACT_GS_LEFT,
            self::IMPACT_GS_RIGHT,
            self::BRAKING_GS_LEFT,
            self::BRAKING_GS_RIGHT,
            self::FOOTSTRIKE_TYPE_LEFT,
            self::FOOTSTRIKE_TYPE_RIGHT,
            self::PRONATION_EXCURSION_LEFT,
            self::PRONATION_EXCURSION_RIGHT,
            self::PERFORMANCE_CONDITION,
            self::RESPIRATION_RATE,
            self::PAUSES,
        ];
    }

    /**
     * Properties
     */
    public function properties(): array
    {
        return array_merge(
            [
                self::PACE,
                self::STRIDE_LENGTH,
                self::VERTICAL_RATIO,
            ],
            self::allDatabaseProperties()
        );
    }

    /**
     * Is the property an array?
     */
    public function isArray(string $key): bool
    {
        return $key !== self::PAUSES && $key !== self::ACTIVITYID;
    }

    /**
     * Can be null?
     */
    protected function canBeNull(string $key): bool
    {
        return match ($key) {
            self::TIME, self::DISTANCE, self::HEARTRATE, self::CADENCE, self::POWER, self::TEMPERATURE, self::GROUNDCONTACT, self::VERTICAL_OSCILLATION, self::GROUNDCONTACT_BALANCE, self::SMO2_0, self::SMO2_1, self::THB_0, self::THB_1, self::IMPACT_GS_LEFT, self::IMPACT_GS_RIGHT, self::BRAKING_GS_LEFT, self::BRAKING_GS_RIGHT, self::FOOTSTRIKE_TYPE_LEFT, self::FOOTSTRIKE_TYPE_RIGHT, self::PRONATION_EXCURSION_LEFT, self::PRONATION_EXCURSION_RIGHT, self::PERFORMANCE_CONDITION, self::RESPIRATION_RATE, self::PAUSES => true,
            default => false,
        };
    }

    /**
     * Is not in Database?
     */
    protected function notInDatabase(string $key): bool
    {
        return match ($key) {
            self::PACE, self::STRIDE_LENGTH, self::VERTICAL_RATIO => true,
            default => false,
        };
    }

    /**
     * Ignore a key while checking for emptiness
     */
    protected function ignoreNonEmptyValue(string $key): bool
    {
        return $key === self::ACTIVITYID;
    }

    public function clear(): void
    {
        parent::clear();

        $this->Pauses->clear();
    }

    /**
     * Can set key?
     */
    protected function canSet(string $key): bool
    {
        return $key !== self::PAUSES;
    }

    /**
     * Value at
     *
     * Remark: This method may throw index offsets.
     * @return mixed
     */
    public function at(int $index, string $key)
    {
        if (! isset($this->Data[$key][$index])) {
            throw new \OutOfBoundsException("Undefined offset: {$index}");
        }

        return $this->Data[$key][$index];
    }

    /**
     * Get activity id
     */
    public function activityID(): int
    {
        return $this->Data[self::ACTIVITYID];
    }

    /**
     * Get time
     * @return array unit: [s]
     */
    public function time()
    {
        return $this->Data[self::TIME];
    }

    /**
     * @return int
     */
    public function totalTime()
    {
        if (empty($this->Data[self::TIME])) {
            return 0;
        }

        return $this->Data[self::TIME][$this->numberOfPoints - 1];
    }

    /**
     * Get distance
     * @return array unit: [km]
     */
    public function distance()
    {
        return $this->Data[self::DISTANCE];
    }

    /**
     * @return int
     */
    public function totalDistance()
    {
        return $this->Data[self::DISTANCE][$this->numberOfPoints - 1];
    }

    public function totalPace(): float
    {
        if (! empty($this->Data[self::DISTANCE])) {
            return $this->totalTime() / $this->totalDistance();
        }

        return 0.0;
    }

    /**
     * Get pace
     * @return array unit: [s/km]
     */
    public function pace()
    {
        return $this->Data[self::PACE];
    }

    /**
     * Theoretical pace will force the loop to ignore distance data for average pace
     */
    public function setTheoreticalPace(array $data, bool $flag = true): void
    {
        $this->set(self::PACE, $data);

        $this->hasTheoreticalPace = $flag;
    }

    public function hasTheoreticalPace(): bool
    {
        return $this->hasTheoreticalPace;
    }

    /**
     * Get heart rate
     * @return array unit: [bpm]
     */
    public function heartRate()
    {
        return $this->Data[self::HEARTRATE];
    }

    /**
     * Get cadence
     * @return array unit: [rpm]
     */
    public function cadence()
    {
        return $this->Data[self::CADENCE];
    }

    /**
     * Get stride length
     * @return array unit: [cm]
     */
    public function strideLength()
    {
        return $this->Data[self::STRIDE_LENGTH];
    }

    /**
     * Get power
     * @return array unit: [W]
     */
    public function power()
    {
        return $this->Data[self::POWER];
    }

    /**
     * Get temperature
     * @return array unit: [°C]
     */
    public function temperature()
    {
        return $this->Data[self::TEMPERATURE];
    }

    /**
     * Get ground contact time
     * @return array unit: [ms]
     */
    public function groundcontact()
    {
        return $this->Data[self::GROUNDCONTACT];
    }

    /**
     * Get vertical oscillation
     * @return array unit: [mm]
     */
    public function verticalOscillation()
    {
        return $this->Data[self::VERTICAL_OSCILLATION];
    }

    /**
     * Get ground contact time balance
     * @return array unit: [%*100]
     */
    public function groundContactBalance()
    {
        return $this->Data[self::GROUNDCONTACT_BALANCE];
    }

    /**
     * Get vertical ratio
     * @return array unit: [%]
     */
    public function verticalRatio()
    {
        return $this->Data[self::VERTICAL_RATIO];
    }

    /**
     * Get smo2 (0)
     * @return array unit: [%]
     */
    public function smo2_0()
    {
        return $this->Data[self::SMO2_0];
    }

    /**
     * Get smo2 (11)
     * @return array unit: [%]
     */
    public function smo2_1()
    {
        return $this->Data[self::SMO2_1];
    }

    /**
     * Get thb (0)
     * @return array unit: [%]
     */
    public function thb_0()
    {
        return $this->Data[self::THB_0];
    }

    /**
     * Get thb (1)
     * @return array unit: [%]
     */
    public function thb_1()
    {
        return $this->Data[self::THB_1];
    }

    /**
     * Get impact GS left
     * @return array unit: [G]
     */
    public function impactGsLeft()
    {
        return $this->Data[self::IMPACT_GS_LEFT];
    }

    /**
     * Get impact GS right
     * @return array unit: [G]
     */
    public function impactGsRight()
    {
        return $this->Data[self::IMPACT_GS_RIGHT];
    }

    /**
     * Get braking GS left
     * @return array unit: [G]
     */
    public function brakingGsLeft()
    {
        return $this->Data[self::BRAKING_GS_LEFT];
    }

    /**
     * Get braking GS right
     * @return array unit: [G]
     */
    public function brakingGsRight()
    {
        return $this->Data[self::BRAKING_GS_RIGHT];
    }

    /**
     * Get footstrike type left
     * @return array unit: [G]
     */
    public function footstrikeTypeLeft()
    {
        return $this->Data[self::FOOTSTRIKE_TYPE_LEFT];
    }

    /**
     * Get footstrike type right
     * @return array unit: [G]
     */
    public function footstrikeTypeRight()
    {
        return $this->Data[self::FOOTSTRIKE_TYPE_RIGHT];
    }

    /**
     * Get footstrike type left
     * @return array unit: [°]
     */
    public function pronationExcursionLeft()
    {
        return $this->Data[self::PRONATION_EXCURSION_LEFT];
    }

    /**
     * Get footstrike type right
     * @return array unit: [°]
     */
    public function pronationExcursionRight()
    {
        return $this->Data[self::PRONATION_EXCURSION_RIGHT];
    }

    public function hasRunScribeData(): bool
    {
        return ! empty($this->Data[self::IMPACT_GS_LEFT])
            || ! empty($this->Data[self::IMPACT_GS_RIGHT])
            || ! empty($this->Data[self::BRAKING_GS_LEFT])
            || ! empty($this->Data[self::BRAKING_GS_RIGHT])
            || ! empty($this->Data[self::FOOTSTRIKE_TYPE_LEFT])
            || ! empty($this->Data[self::FOOTSTRIKE_TYPE_RIGHT])
            || ! empty($this->Data[self::PRONATION_EXCURSION_LEFT])
            || ! empty($this->Data[self::PRONATION_EXCURSION_RIGHT]);
    }

    /**
     * Get performance condition
     * @return array unit: [n]
     */
    public function performanceCondition()
    {
        return $this->Data[self::PERFORMANCE_CONDITION];
    }

    /**
     * Get respiration rate
     * @return array unit: [perMinute]
     */
    public function respirationRate()
    {
        return $this->Data[self::RESPIRATION_RATE];
    }

    /**
     * Get pauses
     */
    public function pauses(): \Runalyze\Model\Trackdata\Pauses
    {
        return $this->Pauses;
    }

    /**
     * Are there pauses?
     */
    public function hasPauses(): bool
    {
        return ! $this->Pauses->isEmpty();
    }

    public function calculatePaceArray(): void
    {
        if (! $this->has(self::PACE)) {
            $PaceCalculator = new PaceCalculator($this);
            $PaceCalculator->calculate();

            $this->set(self::PACE, $PaceCalculator->result());
        }
    }

    protected function calculateStrideLengthArray()
    {
        if (! $this->has(self::STRIDE_LENGTH)) {
            $StridesCalculator = new StrideLength\Calculator($this);
            $StridesCalculator->calculate();

            $this->set(self::STRIDE_LENGTH, $StridesCalculator->stridesData());
        }
    }

    protected function calculateVerticalRatioArray()
    {
        if (! $this->has(self::VERTICAL_RATIO)) {
            $RatioCalculator = new VerticalRatioCalculator($this);
            $RatioCalculator->calculate();

            $this->set(self::VERTICAL_RATIO, $RatioCalculator->verticalRatioData());
        }
    }
}
