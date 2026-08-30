<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Services;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;

class UnitSystemFactory
{
    /**
     * @return \Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem
     */
    public function getUnitSystem(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $configurationManager->getList($account)
            ->getUnitSystem();
    }

    public function getDistanceUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getDistanceUnit();
    }

    public function getElevationUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getElevationUnit();
    }

    public function getStrideLengthUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getStrideLengthUnit();
    }

    public function getEnergyUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getEnergyUnit();
    }

    public function getTemperatureUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getTemperatureUnit();
    }

    public function getWindSpeedUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getWindSpeedUnit();
    }

    public function getWeightUnit(ConfigurationManager $configurationManager, ?Account $account = null): \Runalyze\Metrics\Weight\Unit\AbstractWeightUnit
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getWeightUnit();
    }
}
