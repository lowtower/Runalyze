<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Metrics\Common\UnitInterface;

class DisplayableValue implements \Stringable
{
    /**
     * @var mixed
     */
    protected $Value;

    /**
     * @var string
     */
    protected $Unit;

    /**
     * @param mixed $value
     * @param string|UnitInterface $unit
     * @param int $DefaultDecimals
     * @param string $DefaultDecimalPoint
     * @param string $DefaultThousandsSeparator
     */
    public function __construct(
        $value,
        $unit,
        protected $DefaultDecimals = 0,
        protected $DefaultDecimalPoint = '.',
        protected $DefaultThousandsSeparator = ','
    ) {
        if ($unit instanceof UnitInterface) {
            $value = $unit->fromBaseUnit($value);
            $unit = $unit->getAppendix();
        }

        $this->Value = $value;
        $this->Unit = $unit;
    }

    public function __toString(): string
    {
        return $this->getWithUnit();
    }

    /**
     * @param bool|int $decimals
     * @param bool|string $decimalPoint
     * @param bool|string $thousandsSeparator
     * @return string value and unit, separated by '&nbsp;'
     */
    public function getWithUnit($decimals = false, $decimalPoint = false, $thousandsSeparator = false): string
    {
        return $this->getValue($decimals, $decimalPoint, $thousandsSeparator) . '&nbsp;' . $this->getUnit();
    }

    /**
     * @param bool|int $decimals
     * @param bool|string $decimalPoint
     * @param bool|string $thousandsSeparator
     */
    public function getValue($decimals = false, $decimalPoint = false, $thousandsSeparator = false): string
    {
        return number_format(
            $this->Value,
            $decimals === false ? $this->DefaultDecimals : $decimals,
            $decimalPoint === false ? $this->DefaultDecimalPoint : $decimalPoint,
            $thousandsSeparator === false ? $this->DefaultThousandsSeparator : $thousandsSeparator
        );
    }

    public function setRawValue($value): self
    {
        $this->Value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRawValue()
    {
        return $this->Value;
    }

    /**
     * @return string
     */
    public function getUnit()
    {
        return $this->Unit;
    }

    public function isZero(): bool
    {
        return $this->Value == 0;
    }
}
