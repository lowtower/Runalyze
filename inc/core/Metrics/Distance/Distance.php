<?php

namespace Runalyze\Metrics\Distance;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Distance\Unit\Kilometer;

class Distance extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return Kilometer::class;
    }
}
