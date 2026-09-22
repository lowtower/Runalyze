<?php

namespace Runalyze\Bundle\CoreBundle\Component\Activity\Tool;

use Runalyze\Mathematics\Distribution\EmpiricalDistribution;
use Runalyze\Mathematics\Distribution\MultipleTimeSeries;
use Runalyze\Model\Trackdata;

class TimeSeriesStatistics
{
    protected Trackdata\Entity $Trackdata;

    protected array $AvailableKeys = [];

    protected ?MultipleTimeSeries $MultipleTimeSeries = null;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(Trackdata\Entity $trackdata)
    {
        if (! $trackdata->has(Trackdata\Entity::TIME)) {
            throw new \InvalidArgumentException('Provided trackdata object must have time array.');
        }

        $this->Trackdata = $trackdata;

        $this->determineAvailableKeys();
        $this->manipulateNonIntegerSeries();
    }

    protected function determineAvailableKeys()
    {
        $potentialKeys = [
            Trackdata\Entity::PACE,
            Trackdata\Entity::HEARTRATE,
            Trackdata\Entity::CADENCE,
            Trackdata\Entity::STRIDE_LENGTH,
            Trackdata\Entity::POWER,
            Trackdata\Entity::GROUNDCONTACT,
            Trackdata\Entity::GROUNDCONTACT_BALANCE,
            Trackdata\Entity::VERTICAL_OSCILLATION,
            Trackdata\Entity::VERTICAL_RATIO,
            Trackdata\Entity::IMPACT_GS_LEFT,
            Trackdata\Entity::IMPACT_GS_RIGHT,
            Trackdata\Entity::BRAKING_GS_LEFT,
            Trackdata\Entity::BRAKING_GS_RIGHT,
            Trackdata\Entity::FOOTSTRIKE_TYPE_LEFT,
            Trackdata\Entity::FOOTSTRIKE_TYPE_RIGHT,
            Trackdata\Entity::PRONATION_EXCURSION_LEFT,
            Trackdata\Entity::PRONATION_EXCURSION_RIGHT,
            Trackdata\Entity::SMO2_0,
            Trackdata\Entity::SMO2_1,
            Trackdata\Entity::THB_0,
            Trackdata\Entity::THB_1,
        ];

        foreach ($potentialKeys as $key) {
            if ($this->Trackdata->has($key)) {
                $this->AvailableKeys[$key] = true;
            }
        }
    }

    protected function manipulateNonIntegerSeries()
    {
        $nonIntegerKeys = [
            Trackdata\Entity::IMPACT_GS_LEFT => 10,
            Trackdata\Entity::IMPACT_GS_RIGHT => 10,
            Trackdata\Entity::BRAKING_GS_LEFT => 10,
            Trackdata\Entity::BRAKING_GS_RIGHT => 10,
            Trackdata\Entity::PRONATION_EXCURSION_LEFT => 10,
            Trackdata\Entity::PRONATION_EXCURSION_RIGHT => 10,
        ];
        $hasNonIntegerKeys = false;

        foreach (array_keys($nonIntegerKeys) as $key) {
            if (isset($this->AvailableKeys[$key])) {
                $hasNonIntegerKeys = true;
                break;
            }
        }

        if ($hasNonIntegerKeys) {
            $this->Trackdata = clone $this->Trackdata;

            foreach ($nonIntegerKeys as $key => $factor) {
                $this->Trackdata->set($key, array_map(fn ($v): int => (int) round($v * $factor), $this->Trackdata->get($key)));
            }
        }
    }

    /**
     * @param float[] $quantiles
     */
    public function calculateStatistics(array $quantiles = []): void
    {
        $dataOfAvailableSeries = [];

        foreach (array_keys($this->AvailableKeys) as $key) {
            $dataOfAvailableSeries[$key] = $this->Trackdata->get($key);
        }

        $this->MultipleTimeSeries = new MultipleTimeSeries();
        $this->MultipleTimeSeries->setQuantiles($quantiles);
        $this->MultipleTimeSeries->generateDistributionsFor($dataOfAvailableSeries, $this->Trackdata->get(Trackdata\Entity::TIME));
    }

    /**
     * @param mixed $key enum from trackdata model class
     */
    public function hasStatisticsFor($key): bool
    {
        return isset($this->AvailableKeys[$key]);
    }

    /**
     * @param mixed $key enum from trackdata model class
     */
    public function getStatisticsFor($key): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution($key);
    }

    public function hasStatisticsForPace(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::PACE);
    }

    public function getStatisticsForPace(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::PACE);
    }

    public function hasStatisticsForHeartRate(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::HEARTRATE);
    }

    public function getStatisticsForHeartRate(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::HEARTRATE);
    }

    public function hasStatisticsForCadence(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::CADENCE);
    }

    public function getStatisticsForCadence(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::CADENCE);
    }

    public function hasStatisticsForStrideLength(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::STRIDE_LENGTH);
    }

    public function getStatisticsForStrideLength(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::STRIDE_LENGTH);
    }

    public function hasStatisticsForPower(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::POWER);
    }

    public function getStatisticsForPower(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::POWER);
    }

    public function hasStatisticsForGroundcontact(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::GROUNDCONTACT);
    }

    public function getStatisticsForGroundcontact(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::GROUNDCONTACT);
    }

    public function hasStatisticsForGroundcontactBalance(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::GROUNDCONTACT_BALANCE);
    }

    public function getStatisticsForGroundcontactBalance(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::GROUNDCONTACT_BALANCE);
    }

    public function hasStatisticsForVerticalOscillation(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::VERTICAL_OSCILLATION);
    }

    public function getStatisticsForVerticalOscillation(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::VERTICAL_OSCILLATION);
    }

    public function hasStatisticsForVerticalRatio(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::VERTICAL_RATIO);
    }

    public function getStatisticsForVerticalRatio(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::VERTICAL_RATIO);
    }

    public function hasStatisticsForSaturatedHemoglobin(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::SMO2_0);
    }

    public function getStatisticsForSaturatedHemoglobin(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::SMO2_0);
    }

    public function hasStatisticsForSaturatedHemoglobin2(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::SMO2_1);
    }

    public function getStatisticsForSaturatedHemoglobin2(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::SMO2_1);
    }

    public function hasStatisticsForTotalHemoglobin(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::THB_0);
    }

    public function getStatisticsForTotalHemoglobin(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::THB_0);
    }

    public function hasStatisticsForTotalHemoglobin2(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::THB_1);
    }

    public function getStatisticsForTotalHemoglobin2(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::THB_1);
    }

    public function hasStatisticsForImpactGsLeft(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::IMPACT_GS_LEFT);
    }

    public function getStatisticsForImpactGsLeft(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::IMPACT_GS_LEFT);
    }

    public function hasStatisticsForImpactGsRight(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::IMPACT_GS_RIGHT);
    }

    public function getStatisticsForImpactGsRight(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::IMPACT_GS_RIGHT);
    }

    public function hasStatisticsForBrakingGsLeft(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::BRAKING_GS_LEFT);
    }

    public function getStatisticsForBrakingGsLeft(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::BRAKING_GS_LEFT);
    }

    public function hasStatisticsForBrakingGsRight(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::BRAKING_GS_RIGHT);
    }

    public function getStatisticsForBrakingGsRight(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::BRAKING_GS_RIGHT);
    }

    public function hasStatisticsForFootstrikeTypeLeft(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::FOOTSTRIKE_TYPE_LEFT);
    }

    public function getStatisticsForFootstrikeTypeLeft(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::FOOTSTRIKE_TYPE_LEFT);
    }

    public function hasStatisticsForFootstrikeTypeRight(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::FOOTSTRIKE_TYPE_RIGHT);
    }

    public function getStatisticsForFootstrikeTypeRight(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::FOOTSTRIKE_TYPE_RIGHT);
    }

    public function hasStatisticsForPronationExcursionLeft(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::PRONATION_EXCURSION_LEFT);
    }

    public function getStatisticsForPronationExcursionLeft(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::PRONATION_EXCURSION_LEFT);
    }

    public function hasStatisticsForPronationExcursionRight(): bool
    {
        return $this->hasStatisticsFor(Trackdata\Entity::PRONATION_EXCURSION_RIGHT);
    }

    public function getStatisticsForPronationExcursionRight(): EmpiricalDistribution
    {
        return $this->MultipleTimeSeries->getDistribution(Trackdata\Entity::PRONATION_EXCURSION_RIGHT);
    }
}
