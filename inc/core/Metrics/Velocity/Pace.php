<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Velocity;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Velocity\Unit\SecondsPerKilometer;

class Pace extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return SecondsPerKilometer::class;
    }
}
