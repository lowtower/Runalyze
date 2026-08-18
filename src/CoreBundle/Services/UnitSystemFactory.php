<?php

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

    /**
     * @return \Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit
     */
    public function getDistanceUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getDistanceUnit();
    }

    /**
     * @return \Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit
     */
    public function getElevationUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getElevationUnit();
    }

    /**
     * @return \Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit
     */
    public function getStrideLengthUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getStrideLengthUnit();
    }

    /**
     * @return \Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit
     */
    public function getEnergyUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getEnergyUnit();
    }

    /**
     * @return \Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit
     */
    public function getTemperatureUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getTemperatureUnit();
    }

    /**
     * @return \Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit
     */
    public function getWindSpeedUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getWindSpeedUnit();
    }

    /**
     * @return \Runalyze\Metrics\Weight\Unit\AbstractWeightUnit
     */
    public function getWeightUnit(ConfigurationManager $configurationManager, ?Account $account = null)
    {
        return $this->getUnitSystem($configurationManager, $account)
            ->getWeightUnit();
    }
}
