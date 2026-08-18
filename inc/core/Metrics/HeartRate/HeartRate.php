<?php

namespace Runalyze\Metrics\HeartRate;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\HeartRate\Unit\BeatsPerMinute;

class HeartRate extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return BeatsPerMinute::class;
    }
}
