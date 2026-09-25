<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class HelperTest extends TestCase
{
    #[DataProvider('provideValuesConsideredUnknown')]
    public function testUnknownReturnsFallbackStringForValuesConsideredUnknown(mixed $value): void
    {
        $this->assertSame('?', Helper::Unknown($value));
    }

    /**
     * @return array<string, null[]|string[]|int[]|0.0[]>
     */
    public static function provideValuesConsideredUnknown(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'integer zero' => [0],
            'float zero' => [0.0],
            'string zero' => ['0'],
            'string zero point zero' => ['0.0'],
        ];
    }

    #[DataProvider('provideValuesConsideredKnown')]
    public function testUnknownReturnsValueCoercedToStringForValuesConsideredKnown(mixed $value, string $expected): void
    {
        $this->assertSame($expected, Helper::Unknown($value));
    }

    /**
     * @return array<string, string[]|int[]>
     */
    public static function provideValuesConsideredKnown(): array
    {
        return [
            'non-empty non-numeric string' => ['abc', 'abc'],
            'numeric string' => ['5', '5'],
            'positive integer is coerced to its string representation' => [5, '5'],
            'negative integer is coerced to its string representation' => [-5, '-5'],
            'whitespace-only string is treated as non-numeric and non-empty' => ['  ', '  '],
            'string containing spaces' => ['test string', 'test string'],
        ];
    }

    public function testUnknownReturnsCustomFallbackStringWhenProvided(): void
    {
        $this->assertSame('n/a', Helper::Unknown(0, 'n/a'));
        $this->assertSame('n/a', Helper::Unknown(null, 'n/a'));
    }

    public function testUnknownDefaultFallbackIsQuestionMark(): void
    {
        $this->assertSame('?', Helper::Unknown(null));
    }

    public function testTwoNumbersAddsLeadingZeroBelowTen(): void
    {
        $this->assertSame('05', Helper::TwoNumbers(5));
        $this->assertSame('00', Helper::TwoNumbers(0));
        $this->assertSame('09', Helper::TwoNumbers(9));
    }

    public function testTwoNumbersDoesNotAddLeadingZeroFromTenUpwards(): void
    {
        $this->assertSame('10', Helper::TwoNumbers(10));
        $this->assertSame('42', Helper::TwoNumbers(42));
    }

    public function testCommaToPointReplacesCommaWithDot(): void
    {
        $this->assertSame('12.5', Helper::CommaToPoint('12,5'));
    }

    public function testCommaToPointLeavesStringWithoutCommaUnchanged(): void
    {
        $this->assertSame('12.5', Helper::CommaToPoint('12.5'));
    }

    public function testCommaToPointReplacesAllCommas(): void
    {
        $this->assertSame('1.234.567', Helper::CommaToPoint('1,234,567'));
    }

    public function testFloorForRoundsDownToNearestFactor(): void
    {
        $this->assertSame(40, Helper::floorFor(47.0, 10));
        $this->assertSame(0, Helper::floorFor(9.9, 10));
    }

    public function testFloorForReturnsExactMultipleUnchanged(): void
    {
        $this->assertSame(50, Helper::floorFor(50.0, 10));
    }

    public function testCeilForRoundsUpToNearestFactor(): void
    {
        $this->assertSame(50, Helper::ceilFor(47.0, 10));
        $this->assertSame(10, Helper::ceilFor(0.1, 10));
    }

    public function testCeilForReturnsExactMultipleUnchanged(): void
    {
        $this->assertSame(50, Helper::ceilFor(50.0, 10));
    }

    public function testArrayTrimTrimsAllStringValues(): void
    {
        $result = Helper::arrayTrim([' foo ', 'bar  ', '  baz']);

        $this->assertSame(['foo', 'bar', 'baz'], $result);
    }

    public function testArrayTrimPreservesKeys(): void
    {
        $result = Helper::arrayTrim([
            'a' => ' foo ',
            'b' => ' bar ',
        ]);

        $this->assertSame([
            'a' => 'foo',
            'b' => 'bar',
        ], $result);
    }
}
