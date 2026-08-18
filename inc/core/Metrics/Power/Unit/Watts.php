<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Power\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Watts extends AbstractPowerUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'W';
    }
}
