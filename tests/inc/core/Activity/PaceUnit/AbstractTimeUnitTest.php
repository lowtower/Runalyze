<?php

namespace Runalyze\Activity\PaceUnit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
class AbstractTimeUnitTest extends TestCase
{
    public function testIsTimeFormatIsAlwaysTrue(): void
    {
        $Unit = $this->createUnitWithFactor(1.0);

        $this->assertTrue($Unit->isTimeFormat());
    }

    public function testIsDecimalFormatIsAlwaysFalse(): void
    {
        $Unit = $this->createUnitWithFactor(1.0);

        $this->assertFalse($Unit->isDecimalFormat());
    }

    public function testDividendForUnitThrowsRuntimeException(): void
    {
        $Unit = $this->createUnitWithFactor(1.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('factorForUnit() must not be used for units in decimal format');

        $Unit->dividendForUnit();
    }

    #[DataProvider('provideFactorsAndSecondsPerKm')]
    public function testRawValueMultipliesSecondsPerKmByFactorForUnit(float $factor, float $secondsPerKm, float $expected): void
    {
        $Unit = $this->createUnitWithFactor($factor);

        $this->assertSame($expected, $Unit->rawValue($secondsPerKm));
    }

    /**
     * @return array<string, float[]>
     */
    public static function provideFactorsAndSecondsPerKm(): array
    {
        return [
            'factor of 1, no change' => [1.0, 300.0, 300.0],
            'factor of 60, converts seconds to minutes' => [1 / 60, 300.0, 5.0],
            'zero seconds per km' => [1.0, 0.0, 0.0],
        ];
    }

    public function testFormatReturnsPlaceholderForZeroSecondsPerKm(): void
    {
        $Unit = $this->createUnitWithFactor(1.0);

        $this->assertSame('-:--', $Unit->format(0.0));
    }

    #[DataProvider('provideFormatCases')]
    public function testFormatDelegatesRoundedProductToDurationFormat(float $factor, float $secondsPerKm, string $expected): void
    {
        $Unit = $this->createUnitWithFactor($factor);

        $this->assertSame($expected, $Unit->format($secondsPerKm));
    }

    /**
     * @return array<string, float[]|string[]>
     */
    public static function provideFormatCases(): array
    {
        return [
            'below one minute, no unit conversion' => [1.0, 45.0, '0:45'],
            'above one hour, no unit conversion' => [1.0, 3661.0, '1:01:01'],
            'factor halves the value' => [0.5, 200.0, '1:40'],
            'factor doubles the value' => [2.0, 40.0, '1:20'],
            'fractional product rounds down before formatting' => [1.0, 125.4, '2:05'],
            'fractional product rounds up before formatting' => [1.0, 125.6, '2:06'],
        ];
    }

    #[DataProvider('provideComparisonCases')]
    public function testCompareReturnsDifferenceBetweenSecondAndFirstPace(float $first, float $second, float $expected): void
    {
        $Unit = $this->createUnitWithFactor(1.0);

        $this->assertSame($expected, $Unit->compare($first, $second));
    }

    /**
     * @return array<string, float[]>
     */
    public static function provideComparisonCases(): array
    {
        return [
            'first pace is larger, result is positive' => [300.0, 250.0, -50.0],
            'first pace is smaller, result is negative' => [250.0, 300.0, 50.0],
            'both paces are equal, result is zero' => [300.0, 300.0, 0.0],
        ];
    }

    private function createUnitWithFactor(float $factor): AbstractTimeUnit
    {
        return new class($factor) extends AbstractTimeUnit {
            public function __construct(
                private readonly float $factor
            ) {
            }

            public function unit(): int
            {
                return 0;
            }

            public function appendix(): string
            {
                return '';
            }

            public function factorForUnit(): float
            {
                return $this->factor;
            }
        };
    }
}
