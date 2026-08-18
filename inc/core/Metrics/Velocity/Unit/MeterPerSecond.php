<?php

namespace Runalyze\Metrics\Velocity\Unit;

class MeterPerSecond extends AbstractPaceInDecimalFormatUnit
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'm/s';
    }

    /**
     * @return float
     */
    public function getDividendFromBaseUnit(): int
    {
        return 1000;
    }
}
