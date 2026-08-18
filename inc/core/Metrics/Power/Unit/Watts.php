<?php

namespace Runalyze\Metrics\Power\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Watts extends AbstractPowerUnit
{
    use BaseUnitTrait;

    /**
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'W';
    }
}
