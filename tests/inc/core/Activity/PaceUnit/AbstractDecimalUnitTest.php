<?php

declare(strict_types=1);

namespace Runalyze\Activity\PaceUnit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AbstractDecimalUnitTest extends TestCase
{
    private string $OriginalDecimalSeparator;

    private int $OriginalDecimals;

    protected function setUp(): void
    {
        $this->OriginalDecimalSeparator = AbstractDecimalUnit::$DecimalSeparator;
        $this->OriginalDecimals = AbstractDecimalUnit::$Decimals;
    }

    protected function tearDown(): void
    {
        AbstractDecimalUnit::$DecimalSeparator = $this->OriginalDecimalSeparator;
        AbstractDecimalUnit::$Decimals = $this->OriginalDecimals;
    }

    public function testIsTimeFormatIsAlwaysFalse(): void
    {
        $Unit = $this->createUnitWithDividend(1.0);

        $this->assertFalse($Unit->isTimeFormat());
    }

    public function testIsDecimalFormatIsAlwaysTrue(): void
    {
        $Unit = $this->createUnitWithDividend(1.0);

        $this->assertTrue($Unit->isDecimalFormat());
    }

    public function testFactorForUnitThrowsRuntimeException(): void
    {
        $Unit = $this->createUnitWithDividend(1.0);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('factorForUnit() must not be used for units in decimal format');

        $Unit->factorForUnit();
    }

    #[DataProvider('provideRawValueCases')]
    public function testRawValueDividesDividendByGivenSecondsPerKm(float $dividend, float $secondsPerKm, float $expected): void
    {
        $Unit = $this->createUnitWithDividend($dividend);

        $this->assertSame($expected, $Unit->rawValue($secondsPerKm));
    }

    /**
     * @return \Iterator<string, array<float>>
     */
    public static function provideRawValueCases(): \Iterator
    {
        yield 'km/h style dividend, 360 s/km' => [3600.0, 360.0, 10.0];
        yield 'km/h style dividend, 300 s/km' => [3600.0, 300.0, 12.0];
        yield 'non-integer result' => [10.0, 4.0, 2.5];
    }

    public function testRawValueReturnsZeroForZeroSecondsPerKmRegardlessOfDividend(): void
    {
        $Unit = $this->createUnitWithDividend(3600.0);

        $this->assertEqualsWithDelta(0.0, $Unit->rawValue(0.0), PHP_FLOAT_EPSILON);
    }

    #[DataProvider('provideFormatCases')]
    public function testFormatUsesConfiguredDecimalsAndSeparator(int $decimals, string $separator, float $dividend, float $secondsPerKm, string $expected): void
    {
        AbstractDecimalUnit::$Decimals = $decimals;
        AbstractDecimalUnit::$DecimalSeparator = $separator;

        $Unit = $this->createUnitWithDividend($dividend);

        $this->assertSame($expected, $Unit->format($secondsPerKm));
    }

    /**
     * @return \Iterator<string, array<(float|int|string)>>
     */
    public static function provideFormatCases(): \Iterator
    {
        yield 'one decimal, comma separator' => [1, ',', 3600.0, 300.0, '12,0'];
        yield 'one decimal, comma separator, rounds up' => [1, ',', 3600.0, 224.0, '16,1'];
        yield 'two decimals, dot separator' => [2, '.', 10.0, 4.0, '2.50'];
        yield 'zero decimals, comma separator' => [0, ',', 3600.0, 300.0, '12'];
    }

    public function testFormatReturnsHardcodedZeroWithConfiguredSeparatorForZeroSecondsPerKm(): void
    {
        AbstractDecimalUnit::$Decimals = 3;
        AbstractDecimalUnit::$DecimalSeparator = ';';

        $Unit = $this->createUnitWithDividend(3600.0);

        $this->assertSame('0;0', $Unit->format(0.0));
    }

    #[DataProvider('provideCompareCases')]
    public function testCompareComputesFormulaBasedOnBothPaces(float $first, float $second, float $expected): void
    {
        $Unit = $this->createUnitWithDividend(1.0);

        $this->assertSame($expected, $Unit->compare($first, $second));
    }

    /**
     * @return \Iterator<string, array<float>>
     */
    public static function provideCompareCases(): \Iterator
    {
        yield 'first pace smaller than second' => [200.0, 300.0, 600.0];
        yield 'first pace larger than second' => [300.0, 200.0, -600.0];
    }

    public function testCompareReturnsZeroForIdenticalPaces(): void
    {
        $Unit = $this->createUnitWithDividend(1.0);

        $this->assertEqualsWithDelta(0.0, $Unit->compare(150.0, 150.0), PHP_FLOAT_EPSILON);
    }

    private function createUnitWithDividend(float $dividend): AbstractDecimalUnit
    {
        return new class($dividend) extends AbstractDecimalUnit {
            public function __construct(
                private readonly float $dividend
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

            public function dividendForUnit(): float
            {
                return $this->dividend;
            }
        };
    }
}
