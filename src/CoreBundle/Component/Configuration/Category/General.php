<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Component\Configuration\Category;

use Runalyze\Parameter\Application\DistanceUnitSystem;
use Runalyze\Parameter\Application\EnergyUnit;
use Runalyze\Parameter\Application\TemperatureUnit;

class General extends AbstractCategory
{
    public function getDefaultVariables(): array
    {
        return [
            'WEEK_START' => '1',
            'DISTANCE_UNIT_SYSTEM' => 'metric',
            'WEIGHT_UNIT' => 'kg',
            'ENERGY_UNIT' => 'kcal',
            'TEMPERATURE_UNIT' => '°C',
            'HEART_RATE_UNIT' => 'hfmax',
            'MAINSPORT' => '1',
            'RUNNINGSPORT' => '1',
        ];
    }

    public function getDistanceUnitSystem(): DistanceUnitSystem
    {
        $unitSystem = new DistanceUnitSystem();
        $unitSystem->set($this->Variables['DISTANCE_UNIT_SYSTEM']);

        return $unitSystem;
    }

    public function getTemperatureUnit(): TemperatureUnit
    {
        $unit = new TemperatureUnit();
        $unit->set($this->Variables['TEMPERATURE_UNIT']);

        return $unit;
    }

    public function getEnergyUnit(): EnergyUnit
    {
        $unit = new EnergyUnit();
        $unit->set($this->Variables['ENERGY_UNIT']);

        return $unit;
    }

    public function getRunningSport(): int
    {
        return (int) $this->Variables['RUNNINGSPORT'];
    }

    public function getMainSport(): int
    {
        return (int) $this->Variables['MAINSPORT'];
    }

    protected function getLegacyCategoryName(): string
    {
        return \Runalyze\Configuration\Category\General::class;
    }
}
