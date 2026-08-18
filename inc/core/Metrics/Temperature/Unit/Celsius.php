<?php

namespace Runalyze\Metrics\Temperature\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Celsius extends AbstractTemperatureUnit
{
    use BaseUnitTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '°C';
    }
}
