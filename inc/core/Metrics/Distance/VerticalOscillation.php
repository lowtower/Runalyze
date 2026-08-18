<?php

namespace Runalyze\Metrics\Distance;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Distance\Unit\Millimeter;

class VerticalOscillation extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return Millimeter::class;
    }
}
