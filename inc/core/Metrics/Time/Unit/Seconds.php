<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Time\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Seconds extends AbstractTimeUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 's';
    }
}
