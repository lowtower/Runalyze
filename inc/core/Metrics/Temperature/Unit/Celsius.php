<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Temperature\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Celsius extends AbstractTemperatureUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '°C';
    }
}
