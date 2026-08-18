<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPer500y extends AbstractPaceInTimeFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/500y';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 0.5 / 1.0936133;
    }
}
