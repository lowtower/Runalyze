<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPerKilometer extends AbstractPaceInTimeFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/km';
    }

    public function getFactorFromBaseUnit(): int
    {
        return 1;
    }
}
