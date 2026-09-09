<?php

namespace Runalyze\Sports\Running\Prognosis;

/**
 * Prognosis by David Cameron
 *
 * Competition prediction based on formulas by David Cameron.
 * General formular: T2 = T1 x (D2 / D1) x (a / b), a and b special formulas.
 * Remark: distances in meters, times in minutes
 * @see http://www.infobarrel.com/Runners_Math_How_to_Predict_Your_Race_Time
 */
class Cameron implements PrognosisInterface
{
    /**
     * @var float [km]
     */
    protected float $ReferenceDistance = 0.0;

    /**
     * @var float [s]
     */
    protected float $ReferenceTime = 0.0;

    /**
     * @param float|int $referenceTime [s]
     * @param float $referenceDistance [km]
     */
    public function __construct($referenceTime = 0.0, float $referenceDistance = 0.0)
    {
        $this->setReferenceResult($referenceDistance, $referenceTime);
    }

    public function areValuesValid(): bool
    {
        return $this->ReferenceDistance > 0.0 && $this->ReferenceTime > 0.0;
    }

    /**
     * @param float $distance [km]
     * @param int|float $timeInSeconds [s]
     */
    public function setReferenceResult(float $distance, $timeInSeconds): self
    {
        $this->ReferenceDistance = $distance;
        $this->ReferenceTime = $timeInSeconds;

        return $this;
    }

    public function getSeconds(float $distance): ?float
    {
        if ($distance <= 0.0 || ! $this->areValuesValid()) {
            return null;
        }

        $minutes = $this->ReferenceTime / 60.0;
        $meterReference = $this->ReferenceDistance * 1000.0;
        $meter = $distance * 1000.0;

        return 60.0 * $minutes * ($meter / $meterReference) * ($this->factorFor($meterReference) / $this->factorFor($meter));
    }

    protected function factorFor(float $distance): float
    {
        return 13.49681 - (0.000030363 * $distance) + (835.7114 / $distance ** 0.7905);
    }
}
