<?php

namespace Runalyze\Metrics\Energy;

use Runalyze\Metrics\Common\AbstractMetric;
use Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit;
use Runalyze\Metrics\Energy\Unit\Kilocalories;

class Energy extends AbstractMetric
{
    /**
     * @codeCoverageIgnore
     */
    public function getBaseUnitClass(): string
    {
        return Kilocalories::class;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value, ?AbstractEnergyUnit $unit = null): self
    {
        $this->Value = $unit instanceof \Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit ? $unit->toBaseUnit($value) : $value;

        return $this;
    }

    /**
     * @param int|float $met metabolic equivalent [kcal/kg/h]
     * @param int|float $bodyMassInKilogram [kg]
     * @param int|float $timeInHours [h]
     */
    public function setByMetabolicEquivalent($met, $bodyMassInKilogram, $timeInHours): self
    {
        $this->Value = $met * $bodyMassInKilogram * $timeInHours;

        return $this;
    }
}
