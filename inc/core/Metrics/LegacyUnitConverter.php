<?php

declare(strict_types=1);

namespace Runalyze\Metrics;

use Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit;
use Runalyze\Metrics\Energy\Unit\EnergyEnum;
use Runalyze\Metrics\HeartRate\Unit\HeartRateEnum;
use Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit;
use Runalyze\Metrics\Temperature\Unit\TemperatureEnum;
use Runalyze\Metrics\Weight\Unit\AbstractWeightUnit;
use Runalyze\Metrics\Weight\Unit\WeightEnum;
use Runalyze\Parameter\Application;

class LegacyUnitConverter
{
    /**
     * @param mixed $legacyEnergyEnum
     * @return AbstractEnergyUnit
     */
    public function getEnergyUnit($legacyEnergyEnum)
    {
        $energyUnitMap = [
            Application\EnergyUnit::KCAL => EnergyEnum::KILOCALORIES,
            Application\EnergyUnit::KJ => EnergyEnum::KILOJOULES,
        ];

        if (isset($energyUnitMap[$legacyEnergyEnum])) {
            return EnergyEnum::get($energyUnitMap[$legacyEnergyEnum]);
        }

        return EnergyEnum::get(EnergyEnum::KILOCALORIES);
    }

    /**
     * @param mixed $legacyHeartRateEnum
     */
    public function getHeartRateUnit($legacyHeartRateEnum, int $maximalHeartRate, int $restingHeartRate): \Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit
    {
        $heartRateUnitMap = [
            Application\HeartRateUnit::BPM => HeartRateEnum::BEATS_PER_MINUTE,
            Application\HeartRateUnit::HRMAX => HeartRateEnum::PERCENT_MAXIMUM,
            Application\HeartRateUnit::HRRESERVE => HeartRateEnum::PERCENT_RESERVE,
        ];

        if (isset($heartRateUnitMap[$legacyHeartRateEnum])) {
            return HeartRateEnum::get($heartRateUnitMap[$legacyHeartRateEnum], $maximalHeartRate, $restingHeartRate);
        }

        return HeartRateEnum::get(HeartRateEnum::BEATS_PER_MINUTE, $maximalHeartRate, $restingHeartRate);
    }

    /**
     * @param mixed $legacyTemperatureEnum
     * @return AbstractTemperatureUnit
     */
    public function getTemperatureUnit($legacyTemperatureEnum)
    {
        $temperatureUnitMap = [
            Application\TemperatureUnit::CELSIUS => TemperatureEnum::CELSIUS,
            Application\TemperatureUnit::FAHRENHEIT => TemperatureEnum::FAHRENHEIT,
        ];

        if (isset($temperatureUnitMap[$legacyTemperatureEnum])) {
            return TemperatureEnum::get($temperatureUnitMap[$legacyTemperatureEnum]);
        }

        return TemperatureEnum::get(TemperatureEnum::CELSIUS);
    }

    /**
     * @param mixed $legacyWeightEnum
     * @return AbstractWeightUnit
     */
    public function getWeightUnit($legacyWeightEnum)
    {
        $weightUnitMap = [
            Application\WeightUnit::KG => WeightEnum::KILOGRAM,
            Application\WeightUnit::POUNDS => WeightEnum::POUNDS,
            Application\WeightUnit::STONES => WeightEnum::STONES,
        ];

        if (isset($weightUnitMap[$legacyWeightEnum])) {
            return WeightEnum::get($weightUnitMap[$legacyWeightEnum]);
        }

        return WeightEnum::get(WeightEnum::KILOGRAM);
    }
}
