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
     */
    public function toBaseUnit($valueInThisUnit): float
    {
        return $valueInThisUnit * $this->MaximalHeartRate;
    }

    /**
     * @param mixed $valueInBaseUnit
     */
    public function fromBaseUnit($valueInBaseUnit): float
    {
        return $valueInBaseUnit / $this->MaximalHeartRate;
    }

    public function getMaximalHeartRate(): int
    {
        return $this->MaximalHeartRate;
    }
}
