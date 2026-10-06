<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Activity\PaceUnit\MinPerKilometer;
use Runalyze\Parameter\Application\PaceUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class PaceTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultConstructor(): void
    {
        $Pace = new Pace(300);

        $this->assertSame(Pace::STANDARD, $Pace->unitEnum());
    }

    public function testKmh(): void
    {
        $Pace = new Pace(300, 1, PaceUnit::KM_PER_H);

        $this->assertSame('12,0&nbsp;km/h', $Pace->valueWithAppendix());
    }

    public function testMeterPerSecond(): void
    {
        $Pace = new Pace(10, 0.027, PaceUnit::M_PER_S);

        $this->assertSame('2,7&nbsp;m/s', $Pace->valueWithAppendix());
    }

    public function testMinPerKm(): void
    {
        $Pace = new Pace(312, 1, PaceUnit::MIN_PER_KM);

        $this->assertSame('5:12/km', $Pace->valueWithAppendix());
    }

    public function testMinPer100m(): void
    {
        $Pace = new Pace(123, 0.1, PaceUnit::MIN_PER_100M);

        $this->assertSame('2:03/100m', $Pace->valueWithAppendix());
    }

    public function testDifferentUnitsForComparison(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $Pace1 = new Pace(300, 1, PaceUnit::MIN_PER_KM);
        $Pace2 = new Pace(360, 1, PaceUnit::MIN_PER_100M);

        $Pace1->compareTo($Pace2);
    }

    public function testKmhComparison(): void
    {
        $Pace1 = new Pace(300, 1, PaceUnit::KM_PER_H);
        $Pace2 = new Pace(360, 1, PaceUnit::KM_PER_H);
        $Pace3 = new Pace(300, 1, PaceUnit::KM_PER_H);
        $Pace4 = new Pace(180, 1, PaceUnit::KM_PER_H);

        $this->assertSame('+2,0', $Pace1->compareTo($Pace2, true));
        $this->assertSame('+0,0', $Pace1->compareTo($Pace3, true));
        $this->assertSame('-8,0', $Pace1->compareTo($Pace4, true));
    }

    public function testMinPerKmComparison(): void
    {
        $Pace1 = new Pace(300, 1, PaceUnit::MIN_PER_KM);
        $Pace2 = new Pace(360, 1, PaceUnit::MIN_PER_KM);
        $Pace3 = new Pace(300, 1, PaceUnit::MIN_PER_KM);
        $Pace4 = new Pace(246, 1, PaceUnit::MIN_PER_KM);

        $this->assertSame('+1:00', $Pace1->compareTo($Pace2, true));
        $this->assertSame('+0:00', $Pace1->compareTo($Pace3, true));
        $this->assertSame('-0:54', $Pace1->compareTo($Pace4, true));
    }

    public function testMethodChaining(): void
    {
        $Pace = new Pace(0, 1, PaceUnit::MIN_PER_KM);

        $this->assertSame('3:00', $Pace->setTime(180)->value());
        $this->assertSame('6:00', $Pace->setDistance(0.5)->value());
    }

    public function testSecondsPerKmRoundsInsteadOfTruncating(): void
    {
        $Pace = new Pace(302, 3, PaceUnit::MIN_PER_KM);

        $this->assertSame(101, $Pace->secondsPerKm());
        $this->assertSame('1:41', $Pace->value());
    }

    public function testIsEmptyIsTrueWhenDistanceIsZero(): void
    {
        $Pace = new Pace(300, 0);

        $this->assertTrue($Pace->isEmpty());
    }

    public function testIsEmptyIsTrueWhenTimeIsZero(): void
    {
        $Pace = new Pace(0, 1);

        $this->assertTrue($Pace->isEmpty());
    }

    public function testIsEmptyIsFalseForPositiveTimeAndDistance(): void
    {
        $Pace = new Pace(300, 1);

        $this->assertFalse($Pace->isEmpty());
    }

    public function testSecondsPerKmIsZeroWhenDistanceIsZero(): void
    {
        $Pace = new Pace(300, 0);

        $this->assertSame(0, $Pace->secondsPerKm());
    }

    public function testFromMinPerKmParsesDurationStringAndSetsDistanceToOne(): void
    {
        $Pace = new Pace(0, 5, PaceUnit::MIN_PER_KM);

        $Pace->fromMinPerKm('3:00');

        $this->assertSame(180, $Pace->secondsPerKm());
        $this->assertSame('3:00', $Pace->value());
    }

    public function testSetUnitChangesUnitObjectDirectly(): void
    {
        $Pace = new Pace(300, 1, PaceUnit::KM_PER_H);

        $Pace->setUnit(new MinPerKilometer());

        $this->assertSame(PaceUnit::MIN_PER_KM, $Pace->unitEnum());
        $this->assertSame('5:00', $Pace->value());
    }

    public function testAsKmPerHourFormatsIndependentlyOfConfiguredUnit(): void
    {
        $Pace = new Pace(300, 1, PaceUnit::MIN_PER_KM);

        $this->assertSame('12,0', $Pace->asKmPerHour());
    }

    public function testAsMinPerKmFormatsIndependentlyOfConfiguredUnit(): void
    {
        $Pace = new Pace(300, 1, PaceUnit::KM_PER_H);

        $this->assertSame('5:00', $Pace->asMinPerKm());
    }

    public function testAsUnitEnumFormatsUsingGivenLegacyUnit(): void
    {
        $Pace = new Pace(312, 1, PaceUnit::MIN_PER_KM);

        $this->assertSame('11,5', $Pace->asUnitEnum((string) PaceUnit::KM_PER_H));
    }

    public function testCompareToReturnsEmptyStringWhenThisPaceIsEmpty(): void
    {
        $Pace1 = new Pace(0, 1, PaceUnit::KM_PER_H);
        $Pace2 = new Pace(360, 1, PaceUnit::KM_PER_H);

        $this->assertSame('', $Pace1->compareTo($Pace2, true));
    }

    public function testCompareToReturnsEmptyStringWhenOtherPaceIsEmpty(): void
    {
        $Pace1 = new Pace(360, 1, PaceUnit::KM_PER_H);
        $Pace2 = new Pace(0, 1, PaceUnit::KM_PER_H);

        $this->assertSame('', $Pace1->compareTo($Pace2, true));
    }

    public function testSetUnitEnumIsCurrentlyANoOp(): void
    {
        $Pace = new Pace(300, 1, PaceUnit::KM_PER_H);

        $Pace->setUnitEnum(PaceUnit::MIN_PER_KM);

        $this->assertSame(PaceUnit::KM_PER_H, $Pace->unitEnum());
    }
}
