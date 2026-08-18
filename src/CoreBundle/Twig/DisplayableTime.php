<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

class DisplayableTime extends DisplayableValue
{
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        parent::__construct($value, '');
    }

    /**
     * @param bool|int $decimals
     * @param bool|string $decimalPoint
     * @param bool|string $thousandsSeparator
     * @return string value
     */
    public function getWithUnit($decimals = false, $decimalPoint = false, $thousandsSeparator = false): string
    {
        return $this->getValue($decimals, $decimalPoint, $thousandsSeparator);
    }

    /**
     * @param bool|int $decimals
     * @param bool|string $decimalPoint
     * @param bool|string $thousandsSeparator
     */
    public function getValue($decimals = false, $decimalPoint = false, $thousandsSeparator = false): string
    {
        return (new UtilityExtension())->duration($this->Value);
    }
}
