<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

class KilometerPerHour extends AbstractPaceInDecimalFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'km/h';
    }

    public function getDividendFromBaseUnit(): int
    {
        return 3600;
    }
}
