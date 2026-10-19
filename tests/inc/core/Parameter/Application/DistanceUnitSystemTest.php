<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class DistanceUnitSystemTest extends TestCase
{
    public function testDefaultConstructorResultsInMetric(): void
    {
        $UnitSystem = new DistanceUnitSystem();

        $this->assertTrue($UnitSystem->isMetric());
        $this->assertFalse($UnitSystem->isImperial());
    }

    public function testConstructingWithImperialResultsInImperial(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL);

        $this->assertTrue($UnitSystem->isImperial());
        $this->assertFalse($UnitSystem->isMetric());
    }

    public function testConstructingWithInvalidValueBypassesValidation(): void
    {
        $UnitSystem = new DistanceUnitSystem('not-a-valid-system');

        $this->assertFalse($UnitSystem->isMetric());
        $this->assertFalse($UnitSystem->isImperial());
        $this->assertSame('not-a-valid-system', $UnitSystem->value());
    }

    public function testSetFallsBackToConstructorDefaultForInvalidValue(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

        $UnitSystem->set(DistanceUnitSystem::IMPERIAL);
        $this->assertTrue($UnitSystem->isImperial());

        $UnitSystem->set('not-a-valid-system');

        $this->assertTrue($UnitSystem->isMetric());
    }

    public function testSetAcceptsValidValue(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

        $UnitSystem->set(DistanceUnitSystem::IMPERIAL);

        $this->assertTrue($UnitSystem->isImperial());
    }

    public function testDistanceToKmFactorIsOneForMetric(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

        $this->assertEqualsWithDelta(1.0, $UnitSystem->distanceToKmFactor(), PHP_FLOAT_EPSILON);
    }

    public function testDistanceToKmFactorIsInverseMileMultiplierForImperial(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL);

        $this->assertSame(1 / DistanceUnitSystem::MILE_MULTIPLIER, $UnitSystem->distanceToKmFactor());
    }

    public function testDistanceToPreferredUnitFactorIsOneForMetric(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

        $this->assertEqualsWithDelta(1.0, $UnitSystem->distanceToPreferredUnitFactor(), PHP_FLOAT_EPSILON);
    }

    public function testDistanceToPreferredUnitFactorIsMileMultiplierForImperial(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL);

        $this->assertSame(DistanceUnitSystem::MILE_MULTIPLIER, $UnitSystem->distanceToPreferredUnitFactor());
    }

    public function testDistanceUnitReturnsKmForMetric(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::METRIC);

        $this->assertSame(DistanceUnitSystem::KM, $UnitSystem->distanceUnit());
    }

    public function testDistanceUnitReturnsMilesForImperial(): void
    {
        $UnitSystem = new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL);

        $this->assertSame(DistanceUnitSystem::MILES, $UnitSystem->distanceUnit());
    }
}
