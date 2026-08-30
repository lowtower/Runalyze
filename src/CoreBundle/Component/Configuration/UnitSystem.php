<?php

namespace Runalyze\Bundle\CoreBundle\Component\Configuration;

use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Metrics\Cadence;
use Runalyze\Metrics\Common\Unit\Factorial;
use Runalyze\Metrics\Distance;
use Runalyze\Metrics\HeartRate\Unit\PercentMaximum;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;
use Runalyze\Metrics\Velocity\Unit\MilesPerHour;
use Runalyze\Parameter\Application\DistanceUnitSystem;

class UnitSystem
{
    protected RunalyzeConfigurationList $Configuration;

    protected AbstractPaceUnit $PaceUnit;

    protected LegacyUnitConverter $LegacyUnitConverter;

    public function __construct(RunalyzeConfigurationList $config)
    {
        $this->Configuration = $config;
        $this->PaceUnit = new KilometerPerHour();
        $this->LegacyUnitConverter = new LegacyUnitConverter();
    }

    public function setPaceUnitFromSport(Sport $sport)
    {
        $this->PaceUnit = $sport->getSpeedUnit();
    }

    public function setPaceUnit(AbstractPaceUnit $paceUnit)
    {
        $this->PaceUnit = $paceUnit;
    }

    public function getPaceUnit(?Sport $sport = null): AbstractPaceUnit
    {
        if ($sport instanceof \Runalyze\Bundle\CoreBundle\Entity\Sport) {
            return $sport->getSpeedUnit();
        }

        return $this->PaceUnit;
    }

    public function getCadenceUnit(): \Runalyze\Metrics\Cadence\Unit\RoundsPerMinute
    {
        return new Cadence\Unit\RoundsPerMinute();
    }

    public function getDistanceUnit(): Distance\Unit\AbstractDistanceUnit
    {
        if ($this->Configuration->get('general.DISTANCE_UNIT_SYSTEM') === DistanceUnitSystem::IMPERIAL) {
            return new Distance\Unit\Miles();
        }

        return new Distance\Unit\Kilometer();
    }

    public function getElevationUnit(): Distance\Unit\AbstractDistanceUnit
    {
        if ($this->Configuration->get('general.DISTANCE_UNIT_SYSTEM') === DistanceUnitSystem::IMPERIAL) {
            return new Distance\Unit\Feet();
        }

        return new Distance\Unit\Meter();
    }

    public function getStrideLengthUnit(): Distance\Unit\AbstractDistanceUnit
    {
        if ($this->Configuration->get('general.DISTANCE_UNIT_SYSTEM') === DistanceUnitSystem::IMPERIAL) {
            return new Distance\Unit\Feet();
        }

        return new Distance\Unit\Meter();
    }

    public function getEnergyUnit(): \Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit
    {
        return $this->LegacyUnitConverter->getEnergyUnit(
            $this->Configuration->get('general.ENERGY_UNIT')
        );
    }

    /**
     * @param int|null $maximalHeartRate [bpm]
     * @param int|null $restingHeartRate [bpm]
     */
    public function getHeartRateUnit(?int $maximalHeartRate = null, ?int $restingHeartRate = null): \Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit
    {
        $maximalHeartRate = $maximalHeartRate ?: $this->Configuration->get('data.HF_MAX');
        $restingHeartRate = $restingHeartRate ?: $this->Configuration->get('data.HF_REST');

        return $this->LegacyUnitConverter->getHeartRateUnit(
            $this->Configuration->get('general.HEART_RATE_UNIT'),
            $maximalHeartRate,
            $restingHeartRate
        );
    }

    /**
     * @param null|int $maximalHeartRate [bpm]
     */
    public function getHeartRateUnitPercentMaximum(?int $maximalHeartRate = null): \Runalyze\Metrics\HeartRate\Unit\PercentMaximum
    {
        return new PercentMaximum($maximalHeartRate ?: $this->Configuration->get('data.HF_MAX'));
    }

    public function getTemperatureUnit(): \Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit
    {
        return $this->LegacyUnitConverter->getTemperatureUnit(
            $this->Configuration->get('general.TEMPERATURE_UNIT')
        );
    }

    public function getWindSpeedUnit(): \Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit
    {
        if ($this->Configuration->get('general.DISTANCE_UNIT_SYSTEM') === DistanceUnitSystem::IMPERIAL) {
            return new MilesPerHour();
        }

        return new KilometerPerHour();
    }

    public function getWeightUnit(): \Runalyze\Metrics\Weight\Unit\AbstractWeightUnit
    {
        return $this->LegacyUnitConverter->getWeightUnit(
            $this->Configuration->get('general.WEIGHT_UNIT')
        );
    }

    public function getVO2maxUnit(?float $correctionFactor = null): \Runalyze\Metrics\Common\Unit\Factorial
    {
        return new Factorial('', $correctionFactor ?: $this->Configuration->getVO2maxCorrectionFactor(), 2);
    }
}
