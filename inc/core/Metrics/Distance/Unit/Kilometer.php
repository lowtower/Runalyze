<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Distance\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;
use Runalyze\Metrics\Common\FormattableUnitInterface;

class Kilometer extends AbstractDistanceUnit implements FormattableUnitInterface
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'km';
    }

    public function getDecimals(): int
    {
        return 2;
    }
}
