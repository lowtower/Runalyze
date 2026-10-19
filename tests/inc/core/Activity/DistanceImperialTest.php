<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\DistanceUnitSystem;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class DistanceImperialTest extends TestCase
{
    #[DataProvider('provideMilesConversionCases')]
    public function testMilesConvertsKilometerUsingMileMultiplier(float $kilometer, float $expectedMiles): void
    {
        $Distance = new Distance($kilometer);

        $this->assertSame($expectedMiles, $Distance->miles());
    }

    /**
     * @return \Iterator<string, array<float>>
     */
    public static function provideMilesConversionCases(): \Iterator
    {
        yield 'one kilometer' => [1.0, 0.621371192];
        yield 'five kilometers' => [5.0, 3.10685596];
    }

    #[DataProvider('provideYardsConversionCases')]
    public function testYardsConvertsKilometerToRoundedYards(float $kilometer, int $expectedYards): void
    {
        $Distance = new Distance($kilometer);

        $this->assertSame($expectedYards, $Distance->yards());
    }

    /**
     * @return \Iterator<string, array<(float|int)>>
     */
    public static function provideYardsConversionCases(): \Iterator
    {
        yield 'one kilometer' => [1.0, 1094];
        yield 'five kilometers' => [5.0, 5468];
    }

    #[DataProvider('provideFeetConversionCases')]
    public function testFeetConvertsKilometerToRoundedFeet(float $kilometer, int $expectedFeet): void
    {
        $Distance = new Distance($kilometer);

        $this->assertSame($expectedFeet, $Distance->feet());
    }

    /**
     * @return \Iterator<string, array<(float|int)>>
     */
    public static function provideFeetConversionCases(): \Iterator
    {
        yield 'one kilometer' => [1.0, 3281];
        yield 'five kilometers' => [5.0, 16404];
    }

    public function testSetMilesConvertsMilesBackToKilometer(): void
    {
        $Distance = new Distance();

        $Distance->setMiles(10.0);

        $this->assertSame(10.0 / DistanceUnitSystem::MILE_MULTIPLIER, $Distance->kilometer());
    }

    public function testUnitReturnsKmForMetricSystem(): void
    {
        $Distance = new Distance(1.0, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame(DistanceUnitSystem::KM, $Distance->unit());
    }

    public function testUnitReturnsMilesForImperialSystem(): void
    {
        $Distance = new Distance(1.0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame(DistanceUnitSystem::MILES, $Distance->unit());
    }

    public function testValueInPreferredUnitReturnsKilometerForMetricSystem(): void
    {
        $Distance = new Distance(10.0, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertEqualsWithDelta(10.0, $Distance->valueInPreferredUnit(), PHP_FLOAT_EPSILON);
    }

    public function testValueInPreferredUnitReturnsMilesForImperialSystem(): void
    {
        $Distance = new Distance(5.0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame($Distance->miles(), $Distance->valueInPreferredUnit());
    }

    public function testSetInPreferredUnitSetsKilometerDirectlyForMetricSystem(): void
    {
        $Distance = new Distance(0.0, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $Distance->setInPreferredUnit(10.0);

        $this->assertEqualsWithDelta(10.0, $Distance->kilometer(), PHP_FLOAT_EPSILON);
    }

    public function testSetInPreferredUnitInterpretsValueAsMilesForImperialSystem(): void
    {
        $Distance = new Distance(0.0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $Distance->setInPreferredUnit(10.0);

        $this->assertSame(10.0 / DistanceUnitSystem::MILE_MULTIPLIER, $Distance->kilometer());
    }

    public function testStringMilesFormatsWithDotDecimalAndCommaThousands(): void
    {
        $Distance = new Distance(1.609, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame('1.0', $Distance->stringMiles(false, 1));
    }

    public function testStringDelegatesToStringMilesForImperialSystem(): void
    {
        $Distance = new Distance(1.0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame($Distance->stringMiles(), $Distance->string());
    }

    public function testStringDelegatesToStringKilometerForMetricSystem(): void
    {
        $Distance = new Distance(1.0, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame($Distance->stringKilometer(), $Distance->string());
    }
}
