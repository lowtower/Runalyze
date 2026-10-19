<?php

namespace Runalyze\Bundle\CoreBundle\Twig;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Metrics\Common\JavaScriptFormatter;
use Runalyze\Metrics\Common\UnitInterface;
use Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit;
use Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit;
use Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit;
use Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnitInPercent;
use Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInTimeFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Weight\Unit\AbstractWeightUnit;

class ValueExtension
{
    protected UnitSystem $UnitSystem;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->UnitSystem = new UnitSystem($configurationManager->getList());
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'value', isSafe: ['html'])]
    public function value(mixed $value, string|UnitInterface $unit, int $defaultDecimals = 0, string $defaultDecimalPoint = '.', string $defaultThousandsSeparator = ','): DisplayableValue
    {
        return new DisplayableValue($value, $unit, $defaultDecimals, $defaultDecimalPoint, $defaultThousandsSeparator);
    }

    /**
     * @param float $kilometer [km]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'distance', isSafe: ['html'])]
    public function distance(float $kilometer, ?AbstractDistanceUnit $unit = null, int $decimals = 2): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getDistanceUnit();

        return new DisplayableValue($kilometer, $unit, $decimals);
    }

    /**
     * @param float $meter [m]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'elevation', isSafe: ['html'])]
    public function elevation(float $meter, ?AbstractDistanceUnit $unit = null): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getElevationUnit();

        return new DisplayableValue($meter / 1000, $unit);
    }

    /**
     * @param float $centimeter [cm]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'strideLength', isSafe: ['html'])]
    public function strideLength(float $centimeter, ?AbstractDistanceUnit $unit = null, int $decimals = 2): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getStrideLengthUnit();

        return new DisplayableValue($centimeter / 100000, $unit, $decimals);
    }

    /**
     * @param float $kcal [kcal]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'energy', isSafe: ['html'])]
    public function energy(float $kcal, ?AbstractEnergyUnit $unit = null): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getEnergyUnit();

        return new DisplayableValue($kcal, $unit);
    }

    /**
     * @param float $bpm [bpm]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'heartRate', isSafe: ['html'])]
    public function heartRate(float $bpm, ?AbstractHeartRateUnit $unit = null): DisplayableValueInPercent|DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getHeartRateUnit();

        if ($unit instanceof AbstractHeartRateUnitInPercent) {
            return new DisplayableValueInPercent($bpm, $unit);
        }

        return new DisplayableValue($bpm, $unit);
    }

    /**
     * @param int $bpm [bpm]
     * @param ?int $bpmMax [bpm]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'heartRatePercentMaximum', isSafe: ['html'])]
    public function heartRatePercentMaximum(int $bpm, ?int $bpmMax = null): DisplayableValueInPercent
    {
        return new DisplayableValueInPercent($bpm, $this->UnitSystem->getHeartRateUnitPercentMaximum($bpmMax));
    }

    /**
     * @param float $baseValue [bpm]
     * @param float $comparisonValue [bpm]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'heartRateComparison')]
    public function heartRateComparison(float $baseValue, float $comparisonValue, ?AbstractHeartRateUnit $unit = null): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getHeartRateUnit();

        return $this->heartRate($unit->compareBaseUnit($baseValue, $comparisonValue), $unit);
    }

    /**
     * @param float $secondsPerKilometer [s/km]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'pace', isSafe: ['html'])]
    public function pace(float $secondsPerKilometer, ?AbstractPaceUnit $unit = null, int $decimals = 1): DisplayablePace|DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getPaceUnit();

        if ($unit instanceof AbstractPaceInTimeFormatUnit) {
            return new DisplayablePace($secondsPerKilometer, $unit);
        }

        return new DisplayableValue($secondsPerKilometer, $unit, $decimals);
    }

    /**
     * @param float $baseValue [s/km]
     * @param float $comparisonValue [s/km]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'paceComparison')]
    public function paceComparison(float $baseValue, float $comparisonValue, ?AbstractPaceUnit $unit = null): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getPaceUnit();

        return $this->pace($unit->compareBaseUnit($baseValue, $comparisonValue), $unit);
    }

    /**
     * @param float $celsius [°C]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'temperature', isSafe: ['html'])]
    public function temperature(float $celsius, ?AbstractTemperatureUnit $unit = null, int $decimals = 0): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getTemperatureUnit();

        return new DisplayableValue($celsius, $unit, $decimals);
    }

    /**
     * @param float $kilogram [kg]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'weight', isSafe: ['html'])]
    public function weight($kilogram, ?AbstractWeightUnit $unit = null, int $decimals = 1): DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getWeightUnit();

        return new DisplayableValue($kilogram, $unit, $decimals);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'vo2max')]
    public function vo2max(float $uncorrectedValue, RunalyzeConfigurationList $configurationList, bool $valueIsUsedForShape = true): DisplayableVO2max
    {
        return new DisplayableVO2max($uncorrectedValue, $configurationList, $valueIsUsedForShape);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'vo2maxFor')]
    public function vo2maxFor(ActivityContext $activityContext, RunalyzeConfigurationList $configurationList): DisplayableVO2max
    {
        return $this->vo2max(
            $activityContext->getDecorator()
                ->getUncorrectedVO2max($configurationList),
            $configurationList,
            $activityContext->getActivity()
                ->getUseVO2max()
        );
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'jsFormatter', isSafe: ['html'])]
    public function jsFormatter(UnitInterface $unit): string
    {
        return JavaScriptFormatter::getFormatter($unit);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'jsTransformer', isSafe: ['html'])]
    public function jsTransformer(UnitInterface $unit): string
    {
        return JavaScriptFormatter::getTransformer($unit);
    }
}
