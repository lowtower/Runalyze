<?php

namespace Runalyze\Parser\Activity\Common\Data;

class ContinuousData
{
    /**
     * @var array [s]
     */
    public array $Time = [];

    /**
     * @var array [km]
     */
    public array $Distance = [];

    /**
     * @var array [°]
     */
    public array $Latitude = [];

    /**
     * @var array [°]
     */
    public array $Longitude = [];

    /**
     * @var array [m]
     */
    public array $Altitude = [];

    public bool $IsAltitudeDataBarometric = false;

    /**
     * @var array [bpm]
     */
    public array $HeartRate = [];

    /**
     * @var array [rpm]
     */
    public array $Cadence = [];

    /**
     * @var array [W]
     */
    public array $Power = [];

    /**
     * @var array [°C]
     */
    public array $Temperature = [];

    /**
     * @var array [ms]
     */
    public array $GroundContactTime = [];

    /**
     * @var array [mm]
     */
    public array $VerticalOscillation = [];

    /**
     * @var array [%ooL]
     */
    public array $GroundContactBalance = [];

    /**
     * @var array [%SmO2]
     */
    public array $MuscleOxygenation = [];

    /**
     * @var array [%SmO2]
     */
    public array $MuscleOxygenation_2 = [];

    /**
     * @var array [100 * g/dL]
     */
    public array $TotalHaemoglobin = [];

    /**
     * @var array [100 * g/dL]
     */
    public array $TotalHaemoglobin_2 = [];

    /**
     * @var float[] [G]
     */
    public array $ImpactGsLeft = [];

    /**
     * @var float[] [G]
     */
    public array $ImpactGsRight = [];

    /**
     * @var float[] [G]
     */
    public array $BrakingGsLeft = [];

    /**
     * @var float[] [G]
     */
    public array $BrakingGsRight = [];

    /**
     * @var int[] [°]
     */
    public array $FootstrikeTypeLeft = [];

    /**
     * @var int[] [°]
     */
    public array $FootstrikeTypeRight = [];

    /**
     * @var float[] [°]
     */
    public array $PronationExcursionLeft = [];

    /**
     * @var float[] [°]
     */
    public array $PronationExcursionRight = [];

    /**
     * @var int[] [n]
     */
    public array $PerformanceCondition = [];

    /**
     * @var float[] [pm]
     */
    public array $RespirationRate = [];

    /**
     * @var array [%ooL]
     */
    public array $LeftRightBalance = [];

    /**
     * @var array [-]
     */
    public array $Strokes = [];

    /**
     * @see \Runalyze\Profile\FitSdk\StrokeTypeProfile
     */
    public array $StrokeType = [];

    /**
     * @return array<int, string>
     */
    public function getPropertyNamesOfArrays(): array
    {
        return [
            'Time',
            'Distance',
            'Latitude',
            'Longitude',
            'Altitude',
            'HeartRate',
            'Cadence',
            'Power',
            'Temperature',
            'GroundContactTime',
            'VerticalOscillation',
            'GroundContactBalance',
            'MuscleOxygenation',
            'MuscleOxygenation_2',
            'TotalHaemoglobin',
            'TotalHaemoglobin_2',
            'ImpactGsLeft',
            'ImpactGsRight',
            'BrakingGsLeft',
            'BrakingGsRight',
            'FootstrikeTypeLeft',
            'FootstrikeTypeRight',
            'PronationExcursionLeft',
            'PronationExcursionRight',
            'PerformanceCondition',
            'RespirationRate',
            'LeftRightBalance',
            'Strokes',
            'StrokeType',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getPropertyNamesOfArraysThatShouldNotContainZeros(): array
    {
        return [
            'HeartRate',
            'GroundContactTime',
            'GroundContactBalance',
            'MuscleOxygenation',
            'MuscleOxygenation_2',
            'TotalHaemoglobin',
            'TotalHaemoglobin_2',
            'ImpactGsLeft',
            'ImpactGsRight',
            'BrakingGsLeft',
            'BrakingGsRight',
            'FootstrikeTypeLeft',
            'FootstrikeTypeRight',
            'PronationExcursionLeft',
            'PronationExcursionRight',
            'LeftRightBalance',
        ];
    }

    /**
     * @return int size of first non-empty array property
     */
    public function getLength(): int
    {
        foreach ($this->getPropertyNamesOfArrays() as $property) {
            if (! empty($this->{$property})) {
                return count($this->{$property});
            }
        }

        return 0;
    }

    public function isEmpty(): bool
    {
        foreach ($this->getPropertyNamesOfArrays() as $property) {
            if (! empty($this->{$property})) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return float|null [s]
     */
    public function getTotalDuration(): ?float
    {
        if ($this->Time !== [] && end($this->Time) > 0.0) {
            return end($this->Time);
        }

        return null;
    }

    /**
     * @return float|null [km]
     */
    public function getTotalDistance(): ?float
    {
        if ($this->Distance !== [] && end($this->Distance) > 0.0) {
            return end($this->Distance);
        }

        return null;
    }

    /**
     * @return int|null [bpm]
     */
    public function getMaximalHeartRate(): ?int
    {
        if ($this->HeartRate !== []) {
            return max($this->HeartRate);
        }

        return null;
    }

    /**
     * @return int|null [W]
     */
    public function getMaximalPower(): ?int
    {
        if ($this->Power !== []) {
            return max($this->Power);
        }

        return null;
    }
}
