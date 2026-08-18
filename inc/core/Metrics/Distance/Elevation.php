<?php

namespace Runalyze\Metrics\Distance;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Distance\Unit\Meter;

class Elevation extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return Meter::class;
    }
}
