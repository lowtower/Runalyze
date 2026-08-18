<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Temperature;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Temperature\Unit\Celsius;

class Temperature extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return Celsius::class;
    }
}
