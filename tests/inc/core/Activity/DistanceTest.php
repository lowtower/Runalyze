<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\DistanceUnitSystem;

#[\PHPUnit\Framework\Attributes\Group('default')]
class DistanceTest extends TestCase
{
    private string $OriginalDecimalSeparator;

    private string $OriginalThousandsSeparator;

    private int $OriginalDefaultDecimals;

    protected function setUp(): void
    {
        $this->OriginalDecimalSeparator = Distance::$DecimalSeparator;
        $this->OriginalThousandsSeparator = Distance::$ThousandsSeparator;
        $this->OriginalDefaultDecimals = Distance::$DefaultDecimals;
    }

    protected function tearDown(): void
    {
        Distance::$DecimalSeparator = $this->OriginalDecimalSeparator;
        Distance::$ThousandsSeparator = $this->OriginalThousandsSeparator;
        Distance::$DefaultDecimals = $this->OriginalDefaultDecimals;
    }

    public function testSimpleDistances(): void
    {
        $Distance = new Distance();

        $this->assertTrue($Distance->isZero());
        $this->assertFalse($Distance->isNegative());

        $this->assertEquals(10, $Distance->set(10)->kilometer());
        $this->assertEquals(20, $Distance->multiply(2)->kilometer());
        $this->assertSame(21.1, $Distance->add(new Distance(1.1))->kilometer());
        $this->assertFalse($Distance->isZero());

        $this->assertEqualsWithDelta(-0.9, $Distance->subtract(new Distance(22))->kilometer(), 0.0001);
        $this->assertTrue($Distance->isNegative());
    }

    public function testStaticFormatting(): void
    {
        $this->assertSame('1,000&nbsp;km', Distance::format(1.0));
    }

    public function testAutoFormat(): void
    {
        $Distance = new Distance();

        $this->assertSame('100m', $Distance->set(0.1)->stringAuto());
        $this->assertSame('1.000m', $Distance->set(1.0)->stringAuto());
        $this->assertSame('1.500m', $Distance->set(1.5)->stringAuto());
        $this->assertSame('1,756&nbsp;km', $Distance->set(1.756)->stringAuto());
        $this->assertSame('3.000m', $Distance->set(3.0)->stringAuto());
        $this->assertSame('5,000&nbsp;km', $Distance->set(5)->stringAuto());
    }

    public function testWithoutUnit(): void
    {
        $Distance = new Distance();

        $this->assertEquals('100', $Distance->set(0.1)->stringMeter(false));
        $this->assertSame('0,10', $Distance->set(0.1)->stringKilometer(false, 2));
        $this->assertEquals('1.0', $Distance->set(1.609)->stringMiles(false, 1));
        $this->assertEquals('16.404', $Distance->set(5.0)->stringFeet(false));
        $this->assertEquals('5.468', $Distance->set(5.0)->stringYards(false));
    }

    public function testSetParsesCommaAsDecimalSeparator(): void
    {
        $Distance = new Distance();

        $Distance->set('12,34');

        $this->assertSame(12.34, $Distance->kilometer());
    }

    public function testSetParsesDotAsDecimalSeparator(): void
    {
        $Distance = new Distance();

        $Distance->set('12.34');

        $this->assertSame(12.34, $Distance->kilometer());
    }

    public function testSetAcceptsNumericValueDirectly(): void
    {
        $Distance = new Distance();

        $Distance->set(7.5);

        $this->assertSame(7.5, $Distance->kilometer());
    }

    public function testValueIsAliasForKilometer(): void
    {
        $Distance = new Distance(12.5);

        $this->assertSame($Distance->kilometer(), $Distance->value());
    }

    #[DataProvider('provideMeterConversionCases')]
    public function testMeterConvertsKilometerToRoundedMeters(float $kilometer, int $expectedMeters): void
    {
        $Distance = new Distance($kilometer);

        $this->assertSame($expectedMeters, $Distance->meter());
    }

    /**
     * @return array<string, float[]|int[]>
     */
    public static function provideMeterConversionCases(): array
    {
        return [
            'simple conversion' => [0.1, 100],
            'whole number' => [1.5, 1500],
            'rounds half away from zero, upwards' => [0.1235, 124],
            'rounds half away from zero, negative value' => [-0.1235, -124],
        ];
    }

    public function testMultiplyScalesKilometerAndReturnsSameInstance(): void
    {
        $Distance = new Distance(10.0);

        $this->assertSame($Distance, $Distance->multiply(2.0));
        $this->assertSame(20.0, $Distance->kilometer());
    }

    public function testAddSumsKilometerAndReturnsSameInstance(): void
    {
        $Distance = new Distance(10.0);

        $this->assertSame($Distance, $Distance->add(new Distance(5.0)));
        $this->assertSame(15.0, $Distance->kilometer());
    }

    public function testSubtractReducesKilometerAndReturnsSameInstance(): void
    {
        $Distance = new Distance(10.0);

        $this->assertSame($Distance, $Distance->subtract(new Distance(3.0)));
        $this->assertSame(7.0, $Distance->kilometer());
    }

    public function testIsNegativeIsTrueOnlyForNegativeKilometer(): void
    {
        $this->assertTrue(new Distance(-0.001)->isNegative());
        $this->assertFalse(new Distance(0.0)->isNegative());
        $this->assertFalse(new Distance(0.001)->isNegative());
    }

    public function testIsZeroIsTrueOnlyForZeroKilometer(): void
    {
        $this->assertTrue(new Distance(0.0)->isZero());
        $this->assertFalse(new Distance(0.001)->isZero());
        $this->assertFalse(new Distance(-0.001)->isZero());
    }

    public function testStringKilometerUsesDefaultDecimalsWhenNotGiven(): void
    {
        Distance::$DefaultDecimals = 3;
        Distance::$DecimalSeparator = ',';
        Distance::$ThousandsSeparator = '.';

        $Distance = new Distance(12.3456);

        $this->assertSame('12,346&nbsp;km', $Distance->stringKilometer());
    }

    public function testStringKilometerUsesExplicitlyProvidedDecimals(): void
    {
        Distance::$DecimalSeparator = ',';
        Distance::$ThousandsSeparator = '.';

        $Distance = new Distance(12.3456);

        $this->assertSame('12,35&nbsp;km', $Distance->stringKilometer(true, 2));
    }

    public function testStringKilometerWithoutUnitOmitsSuffix(): void
    {
        Distance::$DecimalSeparator = ',';

        $Distance = new Distance(12.3456);

        $this->assertSame('12,346', $Distance->stringKilometer(false));
    }

    public function testStringMeterIgnoresProvidedDecimalsParameter(): void
    {
        $Distance = new Distance(1.5);

        $this->assertSame(
            $Distance->stringMeter(true),
            $Distance->stringMeter(true)
        );
        $this->assertSame('1.500m', $Distance->stringMeter(true));
    }

    public function testFormatStaticShortcutDelegatesToKilometerString(): void
    {
        Distance::$DecimalSeparator = ',';

        $this->assertSame('1,000&nbsp;km', Distance::format(1.0));
    }

    public function testFormatStaticShortcutRespectsExplicitDecimalsAndNoUnit(): void
    {
        Distance::$DecimalSeparator = ',';

        $this->assertSame('1,50', Distance::format(1.5, false, 2));
    }

    #[DataProvider('provideStringAutoBoundaryCases')]
    public function testStringAutoUsesMeterFormatForSmallOrSpecialDistances(float $kilometer, string $expected): void
    {
        Distance::$DecimalSeparator = ',';

        $Distance = new Distance($kilometer);

        $this->assertSame($expected, $Distance->stringAuto());
    }

    /**
     * @return array<string, float[]|string[]>
     */
    public static function provideStringAutoBoundaryCases(): array
    {
        return [
            'exactly one kilometer uses meter format' => [1.0, '1.000m'],
            'just below one kilometer uses meter format' => [0.999, '999m'],
            'negative distance uses meter format' => [-0.5, '-500m'],
        ];
    }

    public function testStringAutoUsesDefaultStringJustAboveOneKilometer(): void
    {
        Distance::$DecimalSeparator = ',';

        $Distance = new Distance(1.001);

        $this->assertSame('1,001&nbsp;km', $Distance->stringAuto());
    }

    #[DataProvider('provideMilesValues')]
    public function testSetMilesConvertsToExpectedKilometerValue(float $miles, float $expectedKilometer): void
    {
        $Distance = new Distance();

        $Distance->setMiles($miles);

        $this->assertSame($expectedKilometer, $Distance->kilometer());
    }

    /**
     * @return array<string, float[]>
     */
    public static function provideMilesValues(): array
    {
        return [
            'one mile' => [1.0, 1.0 / DistanceUnitSystem::MILE_MULTIPLIER],
            'ten miles' => [10.0, 10.0 / DistanceUnitSystem::MILE_MULTIPLIER],
            'fractional miles' => [3.5, 3.5 / DistanceUnitSystem::MILE_MULTIPLIER],
            'zero miles' => [0.0, 0.0],
            'negative miles' => [-2.0, -2.0 / DistanceUnitSystem::MILE_MULTIPLIER],
        ];
    }

    public function testSetMilesReturnsSameInstanceForChaining(): void
    {
        $Distance = new Distance();

        $this->assertSame($Distance, $Distance->setMiles(5.0));
    }

    public function testSetMilesOverwritesPreviouslySetKilometerValue(): void
    {
        $Distance = new Distance(100.0);

        $Distance->setMiles(1.0);

        $this->assertSame(1.0 / DistanceUnitSystem::MILE_MULTIPLIER, $Distance->kilometer());
    }

    public function testStringMilesWithUnitUsesDefaultDecimalsAndDotSeparator(): void
    {
        $Distance = new Distance(1.609);

        $this->assertSame('1.000&nbsp;mi', $Distance->stringMiles());
    }

    public function testStringMilesWithUnitUsesExplicitDecimals(): void
    {
        $Distance = new Distance(5.0);

        $this->assertSame('3.1&nbsp;mi', $Distance->stringMiles(true, 1));
    }

    public function testStringFeetWithUnitUsesThousandsSeparatorAndNoDecimals(): void
    {
        $Distance = new Distance(5.0);

        $this->assertSame('16.404&nbsp;ft', $Distance->stringFeet());
    }

    public function testStringYardsWithUnitUsesThousandsSeparatorAndNoDecimals(): void
    {
        $Distance = new Distance(5.0);

        $this->assertSame('5.468&nbsp;yd', $Distance->stringYards());
    }

    public function testStringFeetWithUnitForValueBelowThousand(): void
    {
        $Distance = new Distance(0.1);

        $this->assertSame('328&nbsp;ft', $Distance->stringFeet());
    }

    public function testStringYardsWithUnitForValueBelowThousand(): void
    {
        $Distance = new Distance(0.1);

        $this->assertSame('109&nbsp;yd', $Distance->stringYards());
    }
}
