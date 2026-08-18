<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

class MilesPerHour extends AbstractPaceInDecimalFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'mph';
    }

    public function getDividendFromBaseUnit(): float
    {
        return 3600 * 0.621371192;
    }
}
