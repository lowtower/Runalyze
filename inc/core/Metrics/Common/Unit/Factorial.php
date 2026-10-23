<?php

namespace Runalyze\Metrics\Common\Unit;

use Runalyze\Metrics\Common\FormattableUnitInterface;
use Runalyze\Metrics\Common\UnitConversionByFactorTrait;

class Factorial extends Simple implements FormattableUnitInterface
{
    use UnitConversionByFactorTrait;

    #[\Override]
    protected string $Appendix;

    protected int $Decimals;

    /**
     * @param int|float $Factor
     */
    public function __construct(
        string $appendix,
        protected $Factor,
        ?int $decimals = null
    ) {
        parent::__construct($appendix);
        $this->Decimals = $decimals ?: max(0, ceil(log10(1 / $this->Factor)));
    }

    public function getFactorFromBaseUnit(): float
    {
        return $this->Factor;
    }

    public function getDecimals(): int
    {
        return $this->Decimals;
    }
}
