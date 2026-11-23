<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit;
use Symfony\Component\DependencyInjection\Attribute\Target;

class EnergyType extends AbstractUnitBasedType
{
    #[\Override]
    protected int $ViewPrecision = 0;

    public function __construct(#[Target('energyUnit')] AbstractEnergyUnit $energyUnit)
    {
        parent::__construct($energyUnit);
    }
}
