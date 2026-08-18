<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPer100m extends AbstractPaceInTimeFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/100m';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 0.1;
    }
}
