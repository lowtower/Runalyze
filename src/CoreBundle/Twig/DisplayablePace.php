<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;

class DisplayablePace extends DisplayableValue
{
    public function __construct(mixed $value, AbstractPaceInTimeFormatUnit $unit)
    {
        parent::__construct($value, $unit);
    }

    public function getWithUnit(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandsSeparator = null): string
    {
        return $this->getValue($decimals, $decimalPoint, $thousandsSeparator) . $this->getUnit();
    }

    public function getValue(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandsSeparator = null): string
    {
        $isNegative = $this->Value < 0;
        $seconds = round(abs((float) $this->Value));
        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;

        return ($isNegative ? '-' : '') . $minutes . ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);
    }
}
