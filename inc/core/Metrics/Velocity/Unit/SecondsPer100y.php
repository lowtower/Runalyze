<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPer100y extends AbstractPaceInTimeFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/100y';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 0.1 / 1.0936133;
    }
}
