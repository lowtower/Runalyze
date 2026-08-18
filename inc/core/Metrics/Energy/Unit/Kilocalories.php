<?php

declare(strict_types=1);

namespace Runalyze\Metrics\Energy\Unit;

use Runalyze\Metrics\Common\BaseUnitTrait;

class Kilocalories extends AbstractEnergyUnit
{
    use BaseUnitTrait;

    /**
     * @codeCoverageIgnore
     */
    public function getAppendix(): string
    {
        return 'kcal';
    }
}
