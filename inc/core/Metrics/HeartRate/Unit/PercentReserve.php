<?php

declare(strict_types=1);

namespace Runalyze\Metrics\HeartRate\Unit;

class PercentReserve extends AbstractHeartRateUnitInPercent
{
    /**
     * @param int $MaximalHeartRate
     * @param int $RestingHeartRate
     */
    public function __construct(
        protected $MaximalHeartRate,
        protected $RestingHeartRate
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '%HRreserve';
    }

    /**
     * @param mixed $valueInThisUnit
     * @return mixed
     */
    public function toBaseUnit($valueInThisUnit)
    {
        return $this->RestingHeartRate + $valueInThisUnit * ($this->MaximalHeartRate - $this->RestingHeartRate);
    }

    /**
     * @param mixed $valueInBaseUnit
     * @return mixed
     */
    public function fromBaseUnit($valueInBaseUnit)
    {
        return ($valueInBaseUnit - $this->RestingHeartRate) / ($this->MaximalHeartRate - $this->RestingHeartRate);
    }

    /**
     * @return int
     */
    public function getMaximalHeartRate()
    {
        return $this->MaximalHeartRate;
    }

    /**
     * @return int
     */
    public function getRestingHeartRate()
    {
        return $this->RestingHeartRate;
    }
}
