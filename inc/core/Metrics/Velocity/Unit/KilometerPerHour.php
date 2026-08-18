<?php

namespace Runalyze\Metrics\Velocity\Unit;

class KilometerPerHour extends AbstractPaceInDecimalFormatUnit
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'km/h';
    }

    /**
     * @return float
     */
    public function getDividendFromBaseUnit()
    {
        return 3600;
    }
}
