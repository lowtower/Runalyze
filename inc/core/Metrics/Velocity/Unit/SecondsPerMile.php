<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPerMile extends AbstractPaceInTimeFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/mi';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 1.0 / 0.621371192;
    }
}
