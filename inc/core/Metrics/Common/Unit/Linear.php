<?php

namespace Runalyze\Metrics\Common\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitInterface;

class Linear implements UnitInterface, FormattableUnitInterface
{
    /**
     * @var callable
     */
    protected $TransformationFromBaseUnit;

    /**
     * @var callable
     */
    protected $TransformationToBaseUnit;

    public function __construct(
        callable $transformationFromBaseUnit,
        callable $transformationToBaseUnit,
        protected string $Appendix = '',
        protected int $Decimals = 0
    ) {
        $this->TransformationFromBaseUnit = $transformationFromBaseUnit;
        $this->TransformationToBaseUnit = $transformationToBaseUnit;
    }

    public function getAppendix(): string
    {
        return $this->Appendix;
    }

    public function toBaseUnit($valueInThisUnit): mixed
    {
        return call_user_func($this->TransformationToBaseUnit, $valueInThisUnit);
    }

    public function fromBaseUnit($valueInBaseUnit): mixed
    {
        return call_user_func($this->TransformationFromBaseUnit, $valueInBaseUnit);
    }

    public function getDecimals(): int
    {
        return $this->Decimals;
    }
}
