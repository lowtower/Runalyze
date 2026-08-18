<?php

namespace Runalyze\Metrics\Common\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;
use Runalyze\Metrics\Common\UnitInterface;

class None implements UnitInterface
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return '';
    }
}
