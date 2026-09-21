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
     */
    public function toBaseUnit($valueInThisUnit): float
    {
        return $this->RestingHeartRate + $valueInThisUnit * ($this->MaximalHeartRate - $this->RestingHeartRate);
    }

    /**
     * @param mixed $valueInBaseUnit
     */
    public function fromBaseUnit($valueInBaseUnit): float
    {
        return ($valueInBaseUnit - $this->RestingHeartRate) / ($this->MaximalHeartRate - $this->RestingHeartRate);
    }

    public function getMaximalHeartRate(): int
    {
        return $this->MaximalHeartRate;
    }

    public function getRestingHeartRate(): int
    {
        return $this->RestingHeartRate;
    }
}
