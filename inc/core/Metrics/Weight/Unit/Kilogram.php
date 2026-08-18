<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Weight\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Kilogram extends AbstractWeightUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'kg';
    }
}
