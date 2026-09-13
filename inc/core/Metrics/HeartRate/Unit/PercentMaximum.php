<?php

declare(strict_types=1);

namespace Runalyze\Metrics\HeartRate\Unit;

class PercentMaximum extends AbstractHeartRateUnitInPercent
{
    /**
     * @param int $MaximalHeartRate
     */
    public function __construct(
        protected $MaximalHeartRate
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '%HRmax';
    }

    /**
     * @param mixed $valueInThisUnit
     * @return mixed
     */
    public function toBaseUnit($valueInThisUnit)
    {
        return $valueInThisUnit * $this->MaximalHeartRate;
    }

    /**
     * @param mixed $valueInBaseUnit
     * @return mixed
     */
    public function fromBaseUnit($valueInBaseUnit)
    {
        return $valueInBaseUnit / $this->MaximalHeartRate;
    }

    /**
     * @return int
     */
    public function getMaximalHeartRate()
    {
        return $this->MaximalHeartRate;
    }
}
