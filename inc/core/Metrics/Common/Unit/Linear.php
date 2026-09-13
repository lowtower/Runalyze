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

    /**
     * @param string $Appendix
     * @param int $Decimals
     */
    public function __construct(
        callable $transformationFromBaseUnit,
        callable $transformationToBaseUnit,
        protected $Appendix = '',
        protected $Decimals = 0
    ) {
        $this->TransformationFromBaseUnit = $transformationFromBaseUnit;
        $this->TransformationToBaseUnit = $transformationToBaseUnit;
    }

    public function getAppendix()
    {
        return $this->Appendix;
    }

    public function toBaseUnit($valueInThisUnit)
    {
        return call_user_func($this->TransformationToBaseUnit, $valueInThisUnit);
    }

    public function fromBaseUnit($valueInBaseUnit)
    {
        return call_user_func($this->TransformationFromBaseUnit, $valueInBaseUnit);
    }

    public function getDecimals()
    {
        return $this->Decimals;
    }
}
