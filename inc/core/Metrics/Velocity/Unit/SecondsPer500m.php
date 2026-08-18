<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity\Unit;

class SecondsPer500m extends AbstractPaceInTimeFormatUnit
{
    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '/500m';
    }

    public function getFactorFromBaseUnit(): float
    {
        return 0.5;
    }
}
