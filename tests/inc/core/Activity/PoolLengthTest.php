<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Parameter\Application\DistanceUnitSystem;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PoolLengthTest extends \PHPUnit\Framework\TestCase
{
    public function testStaticFunction(): void
    {
        $this->assertEquals('25&nbsp;' . DistanceUnitSystem::METER, PoolLength::format(2500, true));
        $this->assertEquals('25', PoolLength::format(2500, false));
    }

    public function testInMeter(): void
    {
        $PoolLength = new PoolLength();
        $PoolLength->setMeter(50);

        $this->assertEquals(50, $PoolLength->meter());
        $this->assertEquals('50&nbsp;' . DistanceUnitSystem::METER, $PoolLength->stringMeter());
    }

    public function testInCentimeter(): void
    {
        $PoolLength = new PoolLength();
        $PoolLength->set(5000);

        $this->assertEquals(5000, $PoolLength->cm());
        $this->assertEquals('5000&nbsp;' . DistanceUnitSystem::CM, $PoolLength->stringCM());
    }

    public function testInYards(): void
    {
        $PoolLength = new PoolLength();
        $PoolLength->setYards(27.34);

        $this->assertEquals(27.34, $PoolLength->yards());
        $this->assertEquals('27.34&nbsp;' . DistanceUnitSystem::YARDS, $PoolLength->stringYards());
    }

    public function testSettingInPreferredUnit(): void
    {
        $this->assertEquals(25, (new PoolLength(0, new DistanceUnitSystem(DistanceUnitSystem::METRIC)))->setInPreferredUnit(25)->meter());
        $this->assertEquals(54.68, (new PoolLength(0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL)))->setInPreferredUnit(54.68)->yards());
    }

    public function testSetRoundsInsteadOfTruncatingWhenDerivedFromYards(): void
    {
        $PoolLength = new PoolLength();
        $PoolLength->setYards(27.34);

        $this->assertSame(2500, $PoolLength->cm());
    }

    public function testSetAcceptsNumericString(): void
    {
        $PoolLength = new PoolLength();
        $PoolLength->set('2500');

        $this->assertSame(2500, $PoolLength->cm());
        $this->assertSame(2500.0, $PoolLength->value());
    }

    public function testValueIsRawUnroundedFloat(): void
    {
        $PoolLength = new PoolLength();
        $PoolLength->set(2500.4);

        $this->assertSame(2500.4, $PoolLength->value());
        $this->assertSame(2500, $PoolLength->cm());
    }

    public function testCentimeterIsAliasForCm(): void
    {
        $PoolLength = new PoolLength(2500);

        $this->assertSame($PoolLength->cm(), $PoolLength->centimeter());
    }

    public function testUnitReturnsMeterForMetricSystem(): void
    {
        $PoolLength = new PoolLength(2500, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame(DistanceUnitSystem::METER, $PoolLength->unit());
    }

    public function testUnitReturnsYardsForImperialSystem(): void
    {
        $PoolLength = new PoolLength(2500, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame(DistanceUnitSystem::YARDS, $PoolLength->unit());
    }

    public function testValueInPreferredUnitReturnsMeterForMetricSystem(): void
    {
        $PoolLength = new PoolLength(2500, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame(25.0, $PoolLength->valueInPreferredUnit());
    }

    public function testValueInPreferredUnitReturnsYardsForImperialSystem(): void
    {
        $PoolLength = new PoolLength(2500, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame(27.34, $PoolLength->valueInPreferredUnit());
    }

    public function testStringReturnsMeterFormatForMetricSystem(): void
    {
        $PoolLength = new PoolLength(2500, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame($PoolLength->stringMeter(), $PoolLength->string());
    }

    public function testStringReturnsYardsFormatForImperialSystem(): void
    {
        $PoolLength = new PoolLength(2500, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame($PoolLength->stringYards(), $PoolLength->string());
    }
}
