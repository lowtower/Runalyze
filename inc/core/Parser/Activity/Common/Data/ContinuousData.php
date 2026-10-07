<?php

namespace Runalyze\Parser\Activity\Common\Data;

class ContinuousData
{
    /**
     * @var array [s]
     */
    public $Time = [];

    /**
     * @var array [km]
     */
    public $Distance = [];

    /**
     * @var array [°]
     */
    public $Latitude = [];

    /**
     * @var array [°]
     */
    public $Longitude = [];

    /**
     * @var array [m]
     */
    public $Altitude = [];

    public ?bool $IsAltitudeDataBarometric = null;

    /**
     * @var array [bpm]
     */
    public $HeartRate = [];

    /**
     * @var array [rpm]
     */
    public $Cadence = [];

    /**
     * @var array [W]
     */
    public $Power = [];

    /**
     * @var array [°C]
     */
    public $Temperature = [];

    /**
     * @var array [ms]
     */
    public $GroundContactTime = [];

    /**
     * @var array [mm]
     */
    public $VerticalOscillation = [];

    /**
     * @var array [%ooL]
     */
    public $GroundContactBalance = [];

    /**
     * @var array [%SmO2]
     */
    public $MuscleOxygenation = [];

    /**
     * @var array [%SmO2]
     */
    public $MuscleOxygenation_2 = [];

    /**
     * @var array [100 * g/dL]
     */
    public $TotalHaemoglobin = [];

    /**
     * @var array [100 * g/dL]
     */
    public $TotalHaemoglobin_2 = [];

    /**
     * @var float[] [G]
     */
    public $ImpactGsLeft = [];

    /**
     * @var float[] [G]
     */
    public $ImpactGsRight = [];

    /**
     * @var float[] [G]
     */
    public $BrakingGsLeft = [];

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
        return array_all($this->getPropertyNamesOfArrays(), fn (string $property): bool => empty($this->{$property}));
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
