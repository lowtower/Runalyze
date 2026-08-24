<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Cadence;

use Runalyze\Metrics\Cadence\Unit\RoundsPerMinute;
use Runalyze\Metrics\Common\AbstractMetric;

class Cadence extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return RoundsPerMinute::class;
    }
}
