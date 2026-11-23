<?php

namespace Runalyze\Bundle\CoreBundle\Form\Type;

use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Symfony\Component\DependencyInjection\Attribute\Target;

class ElevationType extends AbstractUnitBasedType
{
    #[\Override]
    protected int $ViewPrecision = 0;

    public function __construct(#[Target('elevationUnit')] AbstractDistanceUnit $elevationUnit)
    {
        parent::__construct($elevationUnit);
    }

    public function transform(mixed $value): string
    {
        return parent::transform($value / 1000.00);
    }

    public function reverseTransform($value): mixed
    {
        return parent::reverseTransform($value * 1000.00);
    }
}
