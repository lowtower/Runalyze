<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit;

class EnergyType extends AbstractUnitBasedType
{
    protected int $ViewPrecision = 0;

    public function __construct(AbstractEnergyUnit $energyUnit)
    {
        parent::__construct($energyUnit);
    }
}
