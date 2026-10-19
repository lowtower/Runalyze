<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ElevationTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor(): void
    {
        $this->assertEquals('100', new Elevation(100, new DistanceUnitSystem(DistanceUnitSystem::METRIC))->string(false));
        $this->assertEquals('328', new Elevation(100, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL))->string(false));
    }

    public function testConstructorFallsBackToConfiguredUnitSystemWhenNoneGiven(): void
    {
        Configuration::General()->distanceUnitSystem()->set(DistanceUnitSystem::METRIC);

        $this->assertSame(DistanceUnitSystem::METER, new Elevation(100)->unit());
    }

    public function testSettingInPreferredUnit(): void
    {
        $this->assertSame(100, new Elevation(0, new DistanceUnitSystem(DistanceUnitSystem::METRIC))->setInPreferredUnit(100)->meter());
        $this->assertSame(30, new Elevation(0, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL))->setInPreferredUnit(100)->meter());
    }

    public function testFromMeter(): void
    {
        $Elevation = new Elevation();
        $Elevation->set(100);

        $this->assertSame(100, $Elevation->meter());
        $this->assertSame(328, $Elevation->feet());
    }

    public function testFromFeet(): void
    {
        $Elevation = new Elevation();
        $Elevation->setFeet(100);

        $this->assertSame(30, $Elevation->meter());
        $this->assertSame(100, $Elevation->feet());
    }

    public function testStaticMethod(): void
    {
        Configuration::General()->distanceUnitSystem()->set(DistanceUnitSystem::METRIC);

        $this->assertEquals('123', Elevation::format(123, false));
    }

    /**
     * Regression test: meter() used to truncate (int) $value instead of
     * rounding it, so it gave a different result than feet(), which has
     * always used round(). Both must now be consistent for the same
     * underlying value.
     */
    #[DataProvider('provideRoundingCases')]
    public function testMeterRoundsInsteadOfTruncating(float $value, int $expectedMeter): void
    {
        $Elevation = new Elevation();
        $Elevation->set($value);

        $this->assertSame($expectedMeter, $Elevation->meter());
    }

    /**
     * @return \Iterator<string, array<(float|int)>>
     */
    public static function provideRoundingCases(): \Iterator
    {
        yield 'positive value rounds up' => [30.7, 31];
        yield 'negative value rounds away from zero' => [-30.7, -31];
        yield 'positive half rounds up' => [99.5, 100];
        yield 'negative half rounds away from zero' => [-99.5, -100];
        yield 'positive value rounds down to zero' => [0.4, 0];
        yield 'negative value rounds up to zero' => [-0.4, 0];
    }

    /**
     * set() accepts mixed per ValueInterface, since not every implementation
     * of that interface can restrict itself to float (e.g. HeatIndex accepts
     * a Temperature object). Elevation itself always normalizes the given
     * value to float internally.
     */
    public function testSetAcceptsNumericStringAndCastsToFloat(): void
    {
        $Elevation = new Elevation();
        $Elevation->set('100');

        $this->assertEqualsWithDelta(100.0, $Elevation->value(), PHP_FLOAT_EPSILON);
        $this->assertSame(100, $Elevation->meter());
    }

    public function testValueReturnsRawUnroundedFloat(): void
    {
        $Elevation = new Elevation();
        $Elevation->set(30.7);

        $this->assertEqualsWithDelta(30.7, $Elevation->value(), PHP_FLOAT_EPSILON);
    }

    public function testUnitReturnsMeterForMetricSystem(): void
    {
        $Elevation = new Elevation(100, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame(DistanceUnitSystem::METER, $Elevation->unit());
    }

    public function testUnitReturnsFeetForImperialSystem(): void
    {
        $Elevation = new Elevation(100, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame(DistanceUnitSystem::FEET, $Elevation->unit());
    }

    public function testValueInPreferredUnitReturnsMeterForMetricSystem(): void
    {
        $Elevation = new Elevation(100, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame(100, $Elevation->valueInPreferredUnit());
    }

    public function testValueInPreferredUnitReturnsFeetForImperialSystem(): void
    {
        $Elevation = new Elevation(100, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame(328, $Elevation->valueInPreferredUnit());
    }

    public function testStringMeterFormatsWithThousandsSeparatorAndUnit(): void
    {
        $Elevation = new Elevation(5000, new DistanceUnitSystem(DistanceUnitSystem::METRIC));

        $this->assertSame('5.000&nbsp;m', $Elevation->stringMeter());
    }

    public function testStringFeetFormatsWithThousandsSeparatorAndUnit(): void
    {
        $Elevation = new Elevation(1524, new DistanceUnitSystem(DistanceUnitSystem::IMPERIAL));

        $this->assertSame('5.000&nbsp;ft', $Elevation->stringFeet());
    }
}
