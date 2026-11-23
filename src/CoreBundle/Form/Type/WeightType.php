<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Weight\Unit\AbstractWeightUnit;
use Symfony\Component\DependencyInjection\Attribute\Target;

class WeightType extends AbstractUnitBasedType
{
    #[\Override]
    protected int $ViewPrecision = 1;

    public function __construct(#[Target('weightUnit')] AbstractWeightUnit $weightUnit)
    {
        parent::__construct($weightUnit);
    }
}
