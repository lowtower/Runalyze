<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\HeartRateAverage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\HeartRateMaximum;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Pace;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Vo2max;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Vo2maxWithElevation;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherTemperature;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\WeatherWindSpeed;
use Runalyze\Metrics\Common\Unit\Factorial;
use Runalyze\Metrics\HeartRate\Unit\AbstractHeartRateUnit;
use Runalyze\Metrics\Temperature\Unit\AbstractTemperatureUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceInDecimalFormatUnit;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;

class QueryValueUnitDelegationTest extends TestCase
{
    public function testWeatherWindSpeedDelegatesToUnitSystemsWindSpeedUnit(): void
    {
        $expectedUnit = $this->createStub(AbstractPaceInDecimalFormatUnit::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getWindSpeedUnit')
            ->willReturn($expectedUnit);

        $result = new WeatherWindSpeed()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testHeartRateMaximumDelegatesToUnitSystemsHeartRateUnit(): void
    {
        $expectedUnit = $this->createStub(AbstractHeartRateUnit::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getHeartRateUnit')
            ->willReturn($expectedUnit);

        $result = new HeartRateMaximum()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testHeartRateAverageDelegatesToUnitSystemsHeartRateUnit(): void
    {
        $expectedUnit = $this->createStub(AbstractHeartRateUnit::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getHeartRateUnit')
            ->willReturn($expectedUnit);

        $result = new HeartRateAverage()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testWeatherTemperatureDelegatesToUnitSystemsTemperatureUnit(): void
    {
        $expectedUnit = $this->createStub(AbstractTemperatureUnit::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getTemperatureUnit')
            ->willReturn($expectedUnit);

        $result = new WeatherTemperature()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testPaceDelegatesToUnitSystemsPaceUnit(): void
    {
        $expectedUnit = $this->createStub(AbstractPaceUnit::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getPaceUnit')
            ->willReturn($expectedUnit);

        $result = new Pace()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testVo2maxDelegatesToUnitSystemsVO2maxUnit(): void
    {
        $expectedUnit = $this->createStub(Factorial::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getVO2maxUnit')
            ->willReturn($expectedUnit);

        $result = new Vo2max()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testVo2maxWithElevationInheritsVo2maxsUnitDelegation(): void
    {
        $expectedUnit = $this->createStub(Factorial::class);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('getVO2maxUnit')
            ->willReturn($expectedUnit);

        $result = new Vo2maxWithElevation()->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }
}
