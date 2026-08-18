<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPer100m extends AbstractPaceInTimeFormatUnit
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/100m';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit(): float
    {
        return 0.1;
    }
}
