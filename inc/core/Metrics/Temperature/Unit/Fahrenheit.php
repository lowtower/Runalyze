<?php

namespace Runalyze\Metrics\Temperature\Unit;

class Fahrenheit extends AbstractTemperatureUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '°F';
    }

    /**
     * @param mixed $valueInThisUnit
     */
    public function toBaseUnit($valueInThisUnit): float
    {
        return ($valueInThisUnit - 32) / 1.8;
    }

    /**
     * @param mixed $valueInBaseUnit
     */
    public function fromBaseUnit($valueInBaseUnit): float
    {
        return $valueInBaseUnit * 1.8 + 32;
    }
}
