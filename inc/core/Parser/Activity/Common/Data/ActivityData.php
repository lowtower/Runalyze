<?php

declare(strict_types=1);

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Calculation\Distribution\TrackdataAverages;
use Runalyze\Model\Trackdata;
use Runalyze\Parser\Activity\Bridge\ContinuousDataConverter;
use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

class ActivityData
{
    /**
     * @var float|null [s]
     */
    public ?float $Duration = null;

    /**
     * @var float|null [s]
     */
    public ?float $ElapsedTime = null;

    /**
     * @var float|null [km]
     */
    public ?float $Distance = null;

    /**
     * @var int|null [m]
     */
    public ?int $Elevation = null;

    /**
     * @var int|null [m]
     */
    public ?int $ElevationAscent = null;

    /**
     * @var int|null [m]
     */
    public ?int $ElevationDescent = null;

    /**
     * @var int|null [kcal]
     */
    public ?int $EnergyConsumption = null;

    /**
     * @var int|null [kcal]
     */
    public ?int $EnergyInRest = null;

    public ?int $Trimp = null;

    /**
     * @var int|null [6 .. 20]
     */
    public ?int $RPE = null;

    /**
     * @var int|float|null [W]
     */
    public $AvgPower;

    /**
     * @var int|null [W]
     */
    public ?int $MaxPower = null;

    /**
     * @var float|null [bpm]
     */
    public ?float $AvgHeartRate = null;

    /**
     * @var float|null [bpm]
     */
    public ?float $AvgHeartRateActive = null;

    /**
     * @var int|null [bpm]
     */
    public ?int $MaxHeartRate = null;

    /**
     * @var int|null [bpm]
     */
    public ?int $MaxHeartRateUser = null;

    /**
     * @var array|null [bpm]
     */
    public $HeartRateZoneBounds;

    /**
     * @var int|float|null [rpm]
     */
    public $AvgCadence;

    /**
     * @var int|float|null [ms]
     */
    public $AvgGroundContactTime;

    /**
     * @var int|float|null [%ooL]
     */
    public $AvgGroundContactBalance;

    /**
     * @var int|float|null [mm]
     */
    public $AvgVerticalOscillation;

    /**
     * @var int|float|null [G]
     */
    public $AvgImpactGsLeft;

    /**
     * @var int|float|null [G]
     */
    public $AvgImpactGsRight;

    /**
     * @var int|float|null [G]
     */
    public $AvgBrakingGsLeft;

    /**
     * @var int|float|null [G]
     */
    public $AvgBrakingGsRight;

    /**
     * @var float|null [°]
     */
    public $AvgFootstrikeTypeLeft;

    /**
     * @var float|null [°]
     */
    public $AvgFootstrikeTypeRight;

    /**
     * @var float|null [°]
     */
    public $AvgPronationExcursionLeft;

    /**
     * @var float|null [°]
     */
    public $AvgPronationExcursionRight;

    /**
     * @var int|null
     */
    public $PoolLength;

    /**
     * @var int|null
     */
    public $TotalStrokes;

    /**
     * @var int|null
     */
    public $TotalCycles;

    /**
     * @var int|null [grad] #TSC: add temperature here
     */
    public $AvgTemperature;

    /**
     * @var int|null [-] #TSC: added
     */
    public $AvgRespirationRate;

    /**
     * @var int|null [-] #TSC: added
     */
    public $MaxRespirationRate;

    /**
     * @var array|null [-] #TSC: added
     */
    public $SplitsAdditional;

    /**
     * @var array|null [-] #TSC: added
     */
    public $PaceGoal;

    /**
     * @var bool #TSC
     */
    public $WithGoal = false;

    /**
     * @return array<int, string>
     */
    public function getPropertyNames(): array
    {
        return [
            'Duration',
            'ElapsedTime',
            'Distance',
            'Elevation',
            'ElevationAscent',
            'ElevationDescent',
            'EnergyConsumption',
            'EnergyInRest',
            'Trimp',
            'RPE',
            'AvgPower',
            'MaxPower',
            'AvgHeartRate',
            'AvgHeartRateActive',
            'MaxHeartRate',
            'MaxHeartRateUser',
            'HeartRateZoneBounds',
            'AvgCadence',
            'AvgGroundContactTime',
            'AvgGroundContactBalance',
            'AvgVerticalOscillation',
            'AvgImpactGsLeft',
            'AvgImpactGsRight',
            'AvgBrakingGsLeft',
            'AvgBrakingGsRight',
            'AvgFootstrikeTypeLeft',
            'AvgFootstrikeTypeRight',
            'AvgPronationExcursionLeft',
            'AvgPronationExcursionRight',
            'PoolLength',
            'TotalStrokes',
            'TotalCycles',
            'AvgTemperature',
            'AvgRespirationRate',
            'MaxRespirationRate',
            'SplitsAdditional',
            'PaceGoal',
            'WithGoal',
        ];
    }

    public function completeFromContinuousData(ContinuousData $data, RoundCollection $rounds): void
    {
        $this->completeTotalValuesFromContinuousData($data);
        $this->completeMaximalValuesFromContinuousData($data);
        $this->completeAverageValuesFromContinuousData($data, $rounds);
    }

    public function completeTotalValuesFromContinuousData(ContinuousData $data): void
    {
        $this->Duration ??= $data->getTotalDuration();

        $this->Distance ??= $data->getTotalDistance();
    }

    public function completeMaximalValuesFromContinuousData(ContinuousData $data): void
    {
        $this->MaxHeartRate ??= $data->getMaximalHeartRate();

        $this->MaxPower ??= $data->getMaximalPower();
    }

    public function completeAverageValuesFromContinuousData(ContinuousData $data, RoundCollection $rounds): void
    {
        if ($data->Time === []) {
            return;
        }

        try {
            $averages = new TrackdataAverages(
                new ContinuousDataConverter($data)->convertToLegacyTrackdataModel(),
                [
                    Trackdata\Entity::POWER,
                    Trackdata\Entity::HEARTRATE,
                    Trackdata\Entity::CADENCE,
                    Trackdata\Entity::GROUNDCONTACT,
                    Trackdata\Entity::VERTICAL_OSCILLATION,
                    Trackdata\Entity::GROUNDCONTACT_BALANCE,
                    Trackdata\Entity::IMPACT_GS_LEFT,
                    Trackdata\Entity::IMPACT_GS_RIGHT,
                    Trackdata\Entity::BRAKING_GS_LEFT,
                    Trackdata\Entity::BRAKING_GS_RIGHT,
                    Trackdata\Entity::FOOTSTRIKE_TYPE_LEFT,
                    Trackdata\Entity::FOOTSTRIKE_TYPE_RIGHT,
                    Trackdata\Entity::PRONATION_EXCURSION_LEFT,
                    Trackdata\Entity::PRONATION_EXCURSION_RIGHT,
                ]
            );
        } catch (\InvalidArgumentException) {
            return;
        }

        $this->completeStandardAverageValuesFromAverages($averages);
        $this->completeRunningDynamicsAverageValuesFromAverages($averages);
        $this->completeRunScribeDataAverageValuesFromAverages($averages);

        // #TSC if there are inactive/ruhe rounds, average the hearth-rate only for active rounds
        if ($rounds->hasInactiveRounds()) {
            $activeRoundCalc = new ActiveRoundCalculator($data, $rounds);
            $hr = $activeRoundCalc->calcAvgHeartRate();

            if ($hr != null) {
                $this->AvgHeartRateActive = $hr;
            }
        } else {
            $this->AvgHeartRateActive = $this->AvgHeartRate;
        }
    }

    public function completeStandardAverageValuesFromAverages(TrackdataAverages $averages): void
    {
        $this->AvgPower ??= $averages->average(Trackdata\Entity::POWER);

        $this->AvgHeartRate ??= $averages->average(Trackdata\Entity::HEARTRATE);

        $this->AvgCadence ??= $averages->average(Trackdata\Entity::CADENCE);
    }

    public function completeRunningDynamicsAverageValuesFromAverages(TrackdataAverages $averages): void
    {
        $this->AvgGroundContactTime ??= $averages->average(Trackdata\Entity::GROUNDCONTACT);

        $this->AvgVerticalOscillation ??= $averages->average(Trackdata\Entity::VERTICAL_OSCILLATION);

        $this->AvgGroundContactBalance ??= $averages->average(Trackdata\Entity::GROUNDCONTACT_BALANCE);
    }

    public function completeRunScribeDataAverageValuesFromAverages(TrackdataAverages $averages): void
    {
        $this->AvgImpactGsLeft ??= $averages->average(Trackdata\Entity::IMPACT_GS_LEFT);

        $this->AvgImpactGsRight ??= $averages->average(Trackdata\Entity::IMPACT_GS_RIGHT);

        $this->AvgBrakingGsLeft ??= $averages->average(Trackdata\Entity::BRAKING_GS_LEFT);

        $this->AvgBrakingGsRight ??= $averages->average(Trackdata\Entity::BRAKING_GS_RIGHT);

        $this->AvgFootstrikeTypeLeft ??= $averages->average(Trackdata\Entity::FOOTSTRIKE_TYPE_LEFT);

        $this->AvgFootstrikeTypeRight ??= $averages->average(Trackdata\Entity::FOOTSTRIKE_TYPE_RIGHT);

        $this->AvgPronationExcursionLeft ??= $averages->average(Trackdata\Entity::PRONATION_EXCURSION_LEFT);

        $this->AvgPronationExcursionRight ??= $averages->average(Trackdata\Entity::PRONATION_EXCURSION_RIGHT);
    }

    public function completeFromPauses(PauseCollection $pauses): void
    {
        if ($this->ElapsedTime === null && $this->Duration !== null) {
            $this->ElapsedTime = $this->Duration + $pauses->getTotalDuration();
        }
    }

    public function completeFromRounds(RoundCollection $rounds): void
    {
        if (! $rounds->isEmpty()) {
            $this->Duration ??= $rounds->getTotalDuration();
            $this->Distance ??= $rounds->getTotalDistance();
        }
    }
}
