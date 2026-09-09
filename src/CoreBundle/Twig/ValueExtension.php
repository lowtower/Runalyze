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
            new \Twig\TwigFunction('value', [$this, 'value'], $safeHtmlOptions),
            new \Twig\TwigFunction('distance', [$this, 'distance'], $safeHtmlOptions),
            new \Twig\TwigFunction('elevation', [$this, 'elevation'], $safeHtmlOptions),
            new \Twig\TwigFunction('strideLength', [$this, 'strideLength'], $safeHtmlOptions),
            new \Twig\TwigFunction('energy', [$this, 'energy'], $safeHtmlOptions),
            new \Twig\TwigFunction('heartRate', [$this, 'heartRate'], $safeHtmlOptions),
            new \Twig\TwigFunction('heartRatePercentMaximum', [$this, 'heartRatePercentMaximum'], $safeHtmlOptions),
            new \Twig\TwigFunction('heartRateComparison', [$this, 'heartRateComparison'], $safeHtmlOptions),
            new \Twig\TwigFunction('pace', [$this, 'pace'], $safeHtmlOptions),
            new \Twig\TwigFunction('paceComparison', [$this, 'paceComparison'], $safeHtmlOptions),
            new \Twig\TwigFunction('temperature', [$this, 'temperature'], $safeHtmlOptions),
            new \Twig\TwigFunction('weight', [$this, 'weight'], $safeHtmlOptions),
            new \Twig\TwigFunction('vo2max', [$this, 'vo2max'], $safeHtmlOptions),
            new \Twig\TwigFunction('vo2maxFor', [$this, 'vo2maxFor'], $safeHtmlOptions),
            new \Twig\TwigFunction('jsFormatter', [$this, 'jsFormatter'], $safeHtmlOptions),
            new \Twig\TwigFunction('jsTransformer', [$this, 'jsTransformer'], $safeHtmlOptions),
        ];
    }

    /**
     * @param mixed $value
     * @param string|UnitInterface $unit
     * @param int $defaultDecimals
     * @param string $defaultDecimalPoint
     * @param string $defaultThousandsSeparator
     */
    public function value($value, $unit, $defaultDecimals = 0, $defaultDecimalPoint = '.', $defaultThousandsSeparator = ','): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        return new DisplayableValue($value, $unit, $defaultDecimals, $defaultDecimalPoint, $defaultThousandsSeparator);
    }

    /**
     * @param float|int $kilometer [km]
     * @param null|AbstractDistanceUnit $unit
     * @param int $decimals
     */
    public function distance($kilometer, $unit = null, $decimals = 2): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getDistanceUnit();

        return new DisplayableValue($kilometer, $unit, $decimals);
    }

    /**
     * @param float|int $meter [m]
     * @param null|AbstractDistanceUnit $unit
     */
    public function elevation($meter, $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getElevationUnit();

        return new DisplayableValue($meter / 1000, $unit);
    }

    /**
     * @param float|int $centimeter [cm]
     * @param null|AbstractDistanceUnit $unit
     * @param int $decimals
     */
    public function strideLength($centimeter, $unit = null, $decimals = 2): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getStrideLengthUnit();

        return new DisplayableValue($centimeter / 100000, $unit, $decimals);
    }

    /**
     * @param float|int $kcal [kcal]
     * @param null|AbstractEnergyUnit $unit
     */
    public function energy($kcal, $unit = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getEnergyUnit();

        return new DisplayableValue($kcal, $unit);
    }

    /**
     * @param float|int $bpm [bpm]
     * @param null|AbstractHeartRateUnit $unit
     * @return DisplayableValue
     */
    public function heartRate($bpm, $unit = null)
    {
        $unit = $unit ?: $this->UnitSystem->getHeartRateUnit();

        if ($unit instanceof AbstractHeartRateUnitInPercent) {
            return new DisplayableValueInPercent($bpm, $unit);
        }

        return new DisplayableValue($bpm, $unit);
    }

    /**
     * @param int $bpm [bpm]
     * @param null|int $bpmMax [bpm]
     */
    public function heartRatePercentMaximum($bpm, $bpmMax = null): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValueInPercent
    {
        return new DisplayableValueInPercent($bpm, $this->UnitSystem->getHeartRateUnitPercentMaximum($bpmMax));
    }

    /**
     * @param float|int $baseValue [bpm]
     * @param float|int $comparisonValue [bpm]
     * @param null|AbstractHeartRateUnit $unit
     * @return DisplayableValue
     */
    public function heartRateComparison($baseValue, $comparisonValue, $unit = null)
    {
        $unit = $unit ?: $this->UnitSystem->getHeartRateUnit();

        return $this->heartRate($unit->compareBaseUnit($baseValue, $comparisonValue), $unit);
    }

    /**
     * @param float|int $secondsPerKilometer [s/km]
     * @param null|AbstractPaceUnit $unit
     * @param int $decimals only for pace units in decimal format
     * @return DisplayableValue
     */
    public function pace($secondsPerKilometer, $unit = null, $decimals = 1)
    {
        $unit = $unit ?: $this->UnitSystem->getPaceUnit();

        if ($unit instanceof AbstractPaceInTimeFormatUnit) {
            return new DisplayablePace($secondsPerKilometer, $unit);
        }

        return new DisplayableValue($secondsPerKilometer, $unit, $decimals);
    }

    /**
     * @param float|int $baseValue [s/km]
     * @param float|int $comparisonValue [s/km]
     * @param null|AbstractPaceUnit $unit
     * @return DisplayableValue
     */
    public function paceComparison($baseValue, $comparisonValue, $unit = null)
    {
        $unit = $unit ?: $this->UnitSystem->getPaceUnit();

        return $this->pace($unit->compareBaseUnit($baseValue, $comparisonValue), $unit);
    }

    /**
     * @param float|int $celsius [°C]
     * @param null|AbstractTemperatureUnit $unit
     * @param int $decimals
     */
    public function temperature($celsius, $unit = null, $decimals = 0): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getTemperatureUnit();

        return new DisplayableValue($celsius, $unit, $decimals);
    }

    /**
     * @param float $kilogram [kg]
     * @param null|AbstractWeightUnit $unit
     * @param int $decimals
     */
    public function weight($kilogram, $unit = null, $decimals = 1): \Runalyze\Bundle\CoreBundle\Twig\DisplayableValue
    {
        $unit = $unit ?: $this->UnitSystem->getWeightUnit();

        return new DisplayableValue($kilogram, $unit, $decimals);
    }

    /**
     * @param float $uncorrectedValue
     * @param bool $valueIsUsedForShape
     */
    public function vo2max($uncorrectedValue, RunalyzeConfigurationList $configurationList, $valueIsUsedForShape = true): \Runalyze\Bundle\CoreBundle\Twig\DisplayableVO2max
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
