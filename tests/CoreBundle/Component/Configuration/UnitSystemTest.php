<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Configuration;

use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Metrics\Velocity\Unit\SecondsPer500y;
use Runalyze\Metrics\Velocity\Unit\SecondsPerMile;

class UnitSystemTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllUnitsAreAccessible()
    {
        $unitSystem = new UnitSystem(new RunalyzeConfigurationList());

        $this->assertInstanceOf(\Runalyze\Metrics\Distance\Unit\AbstractDistanceUnit::class, $unitSystem->getDistanceUnit());
        $this->assertInstanceOf(\Runalyze\Metrics\Energy\Unit\AbstractEnergyUnit::class, $unitSystem->getEnergyUnit());
        $this->assertInstanceOf(\Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit::class, $unitSystem->getHeartRateUnit());
        $this->assertInstanceOf(\Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit::class, $unitSystem->getPaceUnit());
        $this->assertInstanceOf(\Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit::class, $unitSystem->getTemperatureUnit());
        $this->assertInstanceOf(\Runalyze\Metrics\Weight\Unit\AbstractWeightUnit::class, $unitSystem->getWeightUnit());
    }

    public function testThatPaceUnitCanBeSet()
    {
        $unitSystem = new UnitSystem(new RunalyzeConfigurationList());
        $unitSystem->setPaceUnit(new SecondsPer500y());

        $this->assertInstanceOf(SecondsPer500y::class, $unitSystem->getPaceUnit());
    }

    public function testThatPaceUnitCanBeSetFromSport()
    {
        $sport = new Sport();
        $sport->setSpeed(PaceEnum::SECONDS_PER_MILE);

        $unitSystem = new UnitSystem(new RunalyzeConfigurationList());
        $unitSystem->setPaceUnitFromSport($sport);

        $this->assertInstanceOf(SecondsPerMile::class, $unitSystem->getPaceUnit());
    }
}
