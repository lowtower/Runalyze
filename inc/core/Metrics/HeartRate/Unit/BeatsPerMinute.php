<?php

declare(strict_types=1);

namespace Runalyze\Metrics\HeartRate\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class BeatsPerMinute extends AbstractHeartRateUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'bpm';
    }
}
