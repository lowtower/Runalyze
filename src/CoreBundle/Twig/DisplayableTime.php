<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

class DisplayableTime extends DisplayableValue
{
    public function __construct(mixed $value)
    {
        parent::__construct($value, '');
    }

    public function getWithUnit(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandsSeparator = null): string
    {
        return $this->getValue($decimals, $decimalPoint, $thousandsSeparator);
    }

    public function getValue(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandsSeparator = null): string
    {
        return (new UtilityExtension())->duration($this->Value);
    }
}
