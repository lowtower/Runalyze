<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPerMile extends AbstractPaceInTimeFormatUnit
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/mi';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit(): float
    {
        return 1.0 / 0.621371192;
    }
}
