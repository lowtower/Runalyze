<?php

namespace Runalyze\Activity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
class LapIntensityTest extends TestCase
{
    #[DataProvider('provideValidFitValues')]
    public function testFromFitToEnumMapsValidValuesToExpectedLabel(float $fitValue, string $expectedLabel): void
    {
        $Intensity = LapIntensity::fromFitToEnum($fitValue);

        $this->assertInstanceOf(\Runalyze\Activity\LapIntensity::class, $Intensity);
        $this->assertSame($expectedLabel, $Intensity->getLabel());
    }

    /**
     * @return array<string, float[]|string[]>
     */
    public static function provideValidFitValues(): array
    {
        return [
            'active' => [0.0, 'Active'],
            'active, fractional value truncates towards zero' => [0.9, 'Active'],
            'rest' => [1.0, 'Rest'],
            'warmup' => [2.0, 'Warmup'],
            'cooldown' => [3.0, 'Cooldown'],
            'recovery' => [4.0, 'Recovery'],
            'interval' => [5.0, 'Interval'],
            'other' => [6.0, 'Other'],
        ];
    }

    #[DataProvider('provideOutOfRangeFitValues')]
    public function testFromFitToEnumThrowsExceptionForOutOfRangeValues(float $fitValue): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('Provided workout_step intensity %s is invalid.', $fitValue));

        LapIntensity::fromFitToEnum($fitValue);
    }

    /**
     * @return array<string, float[]>
     */
    public static function provideOutOfRangeFitValues(): array
    {
        return [
            'clearly negative' => [-1.0],
            'far below range' => [-100.0],
            'fractional value just below zero' => [-0.5],
            'just above max' => [7.0],
            'far above range' => [42.0],
        ];
    }

    public function testFromFitToEnumReturnsSingletonForActive(): void
    {
        $this->assertSame(
            LapIntensity::fromFitToEnum(0.0),
            LapIntensity::fromFitToEnum(0.0)
        );
    }

    public function testFromFitToEnumReturnsSingletonForRest(): void
    {
        $this->assertSame(
            LapIntensity::fromFitToEnum(1.0),
            LapIntensity::fromFitToEnum(1.0)
        );
    }

    public function testFromFitToEnumReturnsNewInstanceForNonSingletonValues(): void
    {
        $this->assertNotSame(
            LapIntensity::fromFitToEnum(2.0),
            LapIntensity::fromFitToEnum(2.0)
        );
    }

    public function testGetInstanceRecoveryDoesNotReuseSingleton(): void
    {
        $this->assertNotSame(
            LapIntensity::getInstanceRecovery(),
            LapIntensity::getInstanceRecovery()
        );
    }

    public function testGetAllReturnsAllSevenIntensitiesInOrder(): void
    {
        $All = LapIntensity::getAll();

        $this->assertCount(7, $All);
        $this->assertSame(
            ['Active', 'Rest', 'Warmup', 'Cooldown', 'Recovery', 'Interval', 'Other'],
            array_map(static fn (LapIntensity $Intensity): string => $Intensity->getLabel(), $All)
        );
    }

    public function testIsActiveIsTrueOnlyForActive(): void
    {
        $this->assertTrue(LapIntensity::fromFitToEnum(0.0)->isActive());
        $this->assertFalse(LapIntensity::fromFitToEnum(1.0)->isActive());
    }

    public function testIsRestIsTrueOnlyForRest(): void
    {
        $this->assertTrue(LapIntensity::fromFitToEnum(1.0)->isRest());
        $this->assertFalse(LapIntensity::fromFitToEnum(0.0)->isRest());
    }

    public function testIsNotRestIsInverseOfIsRest(): void
    {
        $Rest = LapIntensity::fromFitToEnum(1.0);
        $Active = LapIntensity::fromFitToEnum(0.0);

        $this->assertFalse($Rest->isNotRest());
        $this->assertTrue($Active->isNotRest());
    }

    public function testIsEqualToComparesByValueNotByIdentity(): void
    {
        $FirstWarmup = LapIntensity::fromFitToEnum(2.0);
        $SecondWarmup = LapIntensity::fromFitToEnum(2.0);
        $Cooldown = LapIntensity::fromFitToEnum(3.0);

        $this->assertNotSame($FirstWarmup, $SecondWarmup);
        $this->assertTrue($FirstWarmup->isEqualTo($SecondWarmup));
        $this->assertFalse($FirstWarmup->isEqualTo($Cooldown));
    }

    #[DataProvider('provideValidValueStrings')]
    public function testFromValueReturnsMatchingInstanceForValidValues(string $value, string $expectedLabel): void
    {
        $Intensity = LapIntensity::fromValue($value);

        $this->assertInstanceOf(\Runalyze\Activity\LapIntensity::class, $Intensity);
        $this->assertSame($expectedLabel, $Intensity->getLabel());
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideValidValueStrings(): array
    {
        return [
            'active' => ['', 'Active'],
            'rest' => ['R', 'Rest'],
            'warmup' => ['W', 'Warmup'],
            'cooldown' => ['C', 'Cooldown'],
            'recovery' => ['Y', 'Recovery'],
            'interval' => ['I', 'Interval'],
            'other' => ['O', 'Other'],
        ];
    }

    public function testFromValueReturnsNullForInvalidValue(): void
    {
        $Intensity = LapIntensity::fromValue('not-a-valid-value');

        $this->assertNull($Intensity);
    }

    public function testGetLabelReturnsUnknownForValueCreatedOutsideFactories(): void
    {
        $Intensity = new LapIntensity('not-a-valid-value');

        $this->assertSame('Unknown', $Intensity->getLabel());
    }
}
