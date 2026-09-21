<?php

namespace Runalyze\Metrics\Common\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Factorial extends Simple implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    /**
     * @var string
     */
    protected $Appendix;

    /**
     * @var int
     */
    protected $Decimals;

    /**
     * @param string $appendix
     * @param int|float $Factor
     * @param int|null $decimals
     */
    public function __construct(
        $appendix,
        protected $Factor,
        $decimals = null
    ) {
        parent::__construct($appendix);
        $this->Decimals = $decimals ?: max(0, ceil(log10(1 / $this->Factor)));
    }

    public function getFactorFromBaseUnit(): float
    {
        return $this->Factor;
    }

    /**
     * @return int
     */
    public function getDecimals()
    {
        return $this->Decimals;
    }
}
