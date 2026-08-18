<?php

namespace Runalyze\Metrics\Velocity\Unit;

class MeterPerSecond extends AbstractPaceInDecimalFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'm/s';
    }

    public function getDividendFromBaseUnit(): int
    {
        return 1000;
    }
}
