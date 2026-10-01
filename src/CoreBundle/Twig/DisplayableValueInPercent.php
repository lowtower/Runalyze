<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Metrics\Common\UnitInterface;

class DisplayableValueInPercent extends DisplayableValue
{
    public function __construct(mixed $value, string|UnitInterface $unit, int $defaultDecimals = 0, string $defaultDecimalPoint = '.', string $defaultThousandsSeparator = ',')
    {
        parent::__construct($value, $unit, $defaultDecimals, $defaultDecimalPoint, $defaultThousandsSeparator);

        $this->Value *= 100;
    }

    public function getUnit(): string
    {
        return '%';
    }
}
