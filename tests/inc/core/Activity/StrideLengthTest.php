<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Parameter\Application\DistanceUnitSystem;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class StrideLengthTest extends \PHPUnit\Framework\TestCase
{
    public function testStaticFunction(): void
    {
        $this->assertSame('1.23&nbsp;' . DistanceUnitSystem::METER, StrideLength::format(123, true));
        $this->assertEquals('1.23', StrideLength::format(123, false));
    }

    public function testInMeter(): void
    {
        $StrideLength = new StrideLength();
        $StrideLength->setMeter(1.07);

        $this->assertEqualsWithDelta(1.07, $StrideLength->meter(), PHP_FLOAT_EPSILON);
        $this->assertSame('1.07&nbsp;' . DistanceUnitSystem::METER, $StrideLength->stringMeter());
    }

    public function testInCentimeter(): void
    {
        $StrideLength = new StrideLength();
        $StrideLength->set(87);

        $this->assertSame(87, $StrideLength->cm());
        $this->assertSame('87&nbsp;' . DistanceUnitSystem::CM, $StrideLength->stringCM());
    }

    public function testInFeet(): void
    {
        $StrideLength = new StrideLength();
        $StrideLength->setFeet(3.2);

        $this->assertEqualsWithDelta(3.2, $StrideLength->feet(), PHP_FLOAT_EPSILON);
        $this->assertSame('3.2&nbsp;' . DistanceUnitSystem::FEET, $StrideLength->stringFeet());
    }

    public function testSettingInPreferredUnit(): void
    {
        $this->assertEqualsWithDelta(1.23, new StrideLength(0, new DistanceUnitSystem(DistanceUnitSystem::METRIC))->setInPreferredUnit(1.23)->meter(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3.2, new StrideLength(0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL))->setInPreferredUnit(3.2)->feet(), PHP_FLOAT_EPSILON);
    }

    public function testSetRoundsInsteadOfTruncatingWhenDerivedFromFeet(): void
    {
        $StrideLength = new StrideLength();
        $StrideLength->setFeet(3.2);

        $this->assertSame(98, $StrideLength->cm());
    }

    public function testSetAcceptsNumericString(): void
    {
        $StrideLength = new StrideLength();
        $StrideLength->set('87');

        $this->assertSame(87, $StrideLength->cm());
        $this->assertEqualsWithDelta(87.0, $StrideLength->value(), PHP_FLOAT_EPSILON);
    }

    public function testValueIsRawUnroundedFloat(): void
    {
        $StrideLength = new StrideLength();
        $StrideLength->set(87.6);

        $this->assertEqualsWithDelta(87.6, $StrideLength->value(), PHP_FLOAT_EPSILON);
        $this->assertSame(88, $StrideLength->cm());
    }

    public function testCentimeterIsAliasForCm(): void
    {
        $StrideLength = new StrideLength(123);

        $this->assertSame($StrideLength->cm(), $StrideLength->centimeter());
    }

    public function testUnitReturnsMeterForMetricSystem(): void
    {
        $StrideLength = new StrideLength(123, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame(DistanceUnitSystem::METER, $StrideLength->unit());
    }

    public function testUnitReturnsFeetForImperialSystem(): void
    {
        $StrideLength = new StrideLength(123, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame(DistanceUnitSystem::FEET, $StrideLength->unit());
    }

    public function testValueInPreferredUnitReturnsMeterForMetricSystem(): void
    {
        $StrideLength = new StrideLength(123, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertEqualsWithDelta(1.23, $StrideLength->valueInPreferredUnit(), PHP_FLOAT_EPSILON);
    }

    public function testValueInPreferredUnitReturnsFeetForImperialSystem(): void
    {
        $StrideLength = new StrideLength(98, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertEqualsWithDelta(3.22, $StrideLength->valueInPreferredUnit(), PHP_FLOAT_EPSILON);
    }

    public function testStringReturnsMeterFormatForMetricSystem(): void
    {
        $StrideLength = new StrideLength(123, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame($StrideLength->stringMeter(), $StrideLength->string());
    }

    public function testStringReturnsFeetFormatForImperialSystem(): void
    {
        $StrideLength = new StrideLength(98, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame($StrideLength->stringFeet(), $StrideLength->string());
    }
}
