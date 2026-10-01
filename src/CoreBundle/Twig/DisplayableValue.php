<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Metrics\Common\UnitInterface;

class DisplayableValue implements \Stringable
{
    protected mixed $Value;

    protected string $Unit;

    public function __construct(
        mixed $value,
        string|UnitInterface $unit,
        protected int $DefaultDecimals = 0,
        protected string $DefaultDecimalPoint = '.',
        protected string $DefaultThousandsSeparator = ','
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
     * @return string value and unit, separated by '&nbsp;'
     */
    public function getWithUnit(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandsSeparator = null): string
    {
        return $this->getValue($decimals, $decimalPoint, $thousandsSeparator) . '&nbsp;' . $this->getUnit();
    }

    public function getValue(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandsSeparator = null): string
    {
        return number_format(
            $this->Value,
            $decimals ?? $this->DefaultDecimals,
            $decimalPoint ?? $this->DefaultDecimalPoint,
            $thousandsSeparator ?? $this->DefaultThousandsSeparator
        );
    }

    public function setRawValue(mixed $value): self
    {
        $this->Value = $value;

        return $this;
    }

    public function getRawValue(): mixed
    {
        return $this->Value;
    }

    public function getUnit(): string
    {
        return $this->Unit;
    }

    public function isZero(): bool
    {
        return $this->Value == 0;
    }
}
