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

class ValueExtension extends \Twig\Extension\AbstractExtension
{
    protected UnitSystem $UnitSystem;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->UnitSystem = new UnitSystem($configurationManager->getList());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getName(): string
    {
        return 'runalyze.value_extension';
    }

    /**
     * @return \Twig\TwigFunction[]
     *
     * @codeCoverageIgnore
     */
    public function getFunctions(): array
    {
        $safeHtmlOptions = [
            'is_safe' => ['html'],
        ];

        return [
            new \Twig\TwigFunction('value', $this->value(...), $safeHtmlOptions),
            new \Twig\TwigFunction('distance', $this->distance(...), $safeHtmlOptions),
            new \Twig\TwigFunction('elevation', $this->elevation(...), $safeHtmlOptions),
            new \Twig\TwigFunction('strideLength', $this->strideLength(...), $safeHtmlOptions),
            new \Twig\TwigFunction('energy', $this->energy(...), $safeHtmlOptions),
            new \Twig\TwigFunction('heartRate', $this->heartRate(...), $safeHtmlOptions),
            new \Twig\TwigFunction('heartRatePercentMaximum', $this->heartRatePercentMaximum(...), $safeHtmlOptions),
            new \Twig\TwigFunction('heartRateComparison', $this->heartRateComparison(...), $safeHtmlOptions),
            new \Twig\TwigFunction('pace', $this->pace(...), $safeHtmlOptions),
            new \Twig\TwigFunction('paceComparison', $this->paceComparison(...), $safeHtmlOptions),
            new \Twig\TwigFunction('temperature', $this->temperature(...), $safeHtmlOptions),
            new \Twig\TwigFunction('weight', $this->weight(...), $safeHtmlOptions),
            new \Twig\TwigFunction('vo2max', $this->vo2max(...), $safeHtmlOptions),
            new \Twig\TwigFunction('vo2maxFor', $this->vo2maxFor(...), $safeHtmlOptions),
            new \Twig\TwigFunction('jsFormatter', $this->jsFormatter(...), $safeHtmlOptions),
            new \Twig\TwigFunction('jsTransformer', $this->jsTransformer(...), $safeHtmlOptions),
        ];
    }

    public function value(mixed $value, string|UnitInterface $unit, int $defaultDecimals = 0, string $defaultDecimalPoint = '.', string $defaultThousandsSeparator = ','): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        return new DisplayableValue($value, $unit, $defaultDecimals, $defaultDecimalPoint, $defaultThousandsSeparator);
    }

    /**
     * @param float $kilometer [km]
     */
    public function distance(float $kilometer, ?AbstractDistanceUnit $unit = null, int $decimals = 2): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getDistanceUnit();

        return new DisplayableValue($kilometer, $unit, $decimals);
    }

    /**
     * @param float $meter [m]
     */
    public function elevation(float $meter, ?AbstractDistanceUnit $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getElevationUnit();

        return new DisplayableValue($meter / 1000, $unit);
    }

    /**
     * @param float $centimeter [cm]
     */
    public function strideLength(float $centimeter, ?AbstractDistanceUnit $unit = null, int $decimals = 2): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getStrideLengthUnit();

        return new DisplayableValue($centimeter / 100000, $unit, $decimals);
    }

    /**
     * @param float $kcal [kcal]
     */
    public function energy(float $kcal, ?AbstractEnergyUnit $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getEnergyUnit();

        return new DisplayableValue($kcal, $unit);
    }

    /**
     * @param float $bpm [bpm]
     */
    public function heartRate(float $bpm, ?AbstractHeartRateUnit $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValueInPercent|\Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
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
    public function heartRatePercentMaximum(int $bpm, ?int $bpmMax = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValueInPercent
    {
        return new DisplayableValueInPercent($bpm, $this->UnitSystem->getHeartRateUnitPercentMaximum($bpmMax));
    }

    /**
     * @param float $baseValue [bpm]
     * @param float $comparisonValue [bpm]
     */
    public function heartRateComparison(float $baseValue, float $comparisonValue, ?AbstractHeartRateUnit $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getHeartRateUnit();

        return $this->heartRate($unit->compareBaseUnit($baseValue, $comparisonValue), $unit);
    }

    /**
     * @param float $secondsPerKilometer [s/km]
     */
    public function pace(float $secondsPerKilometer, ?AbstractPaceUnit $unit = null, int $decimals = 1): \Runalyze\Bundle\CoreBundle\Twig\DisplayablePace|\Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
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
    public function paceComparison(float $baseValue, float $comparisonValue, ?AbstractPaceUnit $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getPaceUnit();

        return $this->pace($unit->compareBaseUnit($baseValue, $comparisonValue), $unit);
    }

    /**
     * @param float $celsius [°C]
     */
    public function temperature(float $celsius, ?AbstractTemperatureUnit $unit = null, int $decimals = 0): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getTemperatureUnit();

        return new DisplayableValue($celsius, $unit, $decimals);
    }

    /**
     * @param float $kilogram [kg]
     */
    public function weight($kilogram, ?AbstractWeightUnit $unit = null, int $decimals = 1): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getWeightUnit();

        return new DisplayableValue($kilogram, $unit, $decimals);
    }

    public function vo2max(float $uncorrectedValue, RunalyzeConfigurationList $configurationList, bool $valueIsUsedForShape = true): \Runalyze\Bundle\CoreBundle\Twig\DisplayableVO2max
    {
        return new DisplayableVO2max($uncorrectedValue, $configurationList, $valueIsUsedForShape);
    }

    public function vo2maxFor(ActivityContext $activityContext, RunalyzeConfigurationList $configurationList): \Runalyze\Bundle\CoreBundle\Twig\DisplayableVO2max
    {
        return $this->vo2max(
            $activityContext->getDecorator()
                ->getUncorrectedVO2max($configurationList),
            $configurationList,
            $activityContext->getActivity()
                ->getUseVO2max()
        );
    }

    public function jsFormatter(UnitInterface $unit): string
    {
        return JavaScriptFormatter::getFormatter($unit);
    }

    public function jsTransformer(UnitInterface $unit): string
    {
        return JavaScriptFormatter::getTransformer($unit);
    }
}
