<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPer500m extends AbstractPaceInTimeFormatUnit
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/500m';
    }

    /**
     * @return float
     */
    public function getFactorFromBaseUnit(): float
    {
        return 0.5;
    }
}
