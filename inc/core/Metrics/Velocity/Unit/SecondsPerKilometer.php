<?php

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPerKilometer extends AbstractPaceInTimeFormatUnit
{
    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/km';
    }

    /**
     * @return int
     */
    public function getFactorFromBaseUnit(): int
    {
        return 1;
    }
}
