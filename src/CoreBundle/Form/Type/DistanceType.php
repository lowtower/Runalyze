<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Activity\Distance;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Symfony\Component\DependencyInjection\Attribute\Target;

class DistanceType extends AbstractUnitBasedType
{
    public function __construct(#[Target('distanceUnit')] AbstractDistanceUnit $distanceUnit)
    {
        parent::__construct($distanceUnit);

        // #TSC set the same precision as other parts
        $this->ViewPrecision = Distance::$DefaultDecimals;
    }
}
