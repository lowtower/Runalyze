<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\Group('default')]
class DurationTest extends \PHPUnit\Framework\TestCase
{
    public function testFromSeconds(): void
    {
        $Time = new Duration(17.23);

        $this->assertSame(17.23, $Time->seconds());
        $this->assertEquals(127, $Time->fromSeconds(127)->seconds());
    }

    public function testFromString(): void
    {
        $Time = new Duration('1:17:21');

        $this->assertEquals(3600 + 17 * 60 + 21, $Time->seconds());
        $this->assertEquals(17 * 60 + 21, $Time->fromString('17:21')->seconds());
        $this->assertSame(3 * 60 + 54.2, $Time->fromString('3:54,2')->seconds());
        $this->assertEquals(12, $Time->fromString('0:12')->seconds());
        $this->assertSame(10.05, $Time->fromString('10.05')->seconds());
    }

    public function testAddAndSubtract(): void
    {
        $Time = new Duration(100);

        $this->assertEquals(200, $Time->add(new Duration(100))->seconds());
        $this->assertEquals(150, $Time->subtract(new Duration(50))->seconds());
    }

    public function testMultiply(): void
    {
        $Time = new Duration(10);

        $this->assertEquals(20, $Time->multiply(2)->seconds());
    }

    public function testIsFlags(): void
    {
        $Time = new Duration();

        $this->assertTrue($Time->isZero());
        $this->assertFalse($Time->isNegative());

        $Time->fromSeconds(-10);

        $this->assertFalse($Time->isZero());
        $this->assertTrue($Time->isNegative());
    }

    public function testAutoFormat(): void
    {
        $Time = new Duration();

        $this->assertSame('1d 09:13:41', $Time->fromString('1d 09:13:41,4')->string());
        $this->assertSame('1d 09:13:41', $Time->fromString('33:13:41,4')->string());
        $this->assertSame('23:13:41', $Time->fromString('23:13:41,4')->string());
        $this->assertSame('3:13:41', $Time->fromString(' 3:13:41,4')->string());
        $this->assertSame('13:41,40', $Time->fromString('13:41,4')->string());
        $this->assertSame('3:41,40', $Time->fromString(' 3:41,4')->string());
        $this->assertSame('0:41,40', $Time->fromString('   41,4')->string());
        $this->assertSame('13:41', $Time->fromString('13:41')->string());
        $this->assertSame('3:41', $Time->fromString(' 3:41')->string());
        $this->assertSame('0:41', $Time->fromString('   41')->string());
    }

    public function testFormat(): void
    {
        $Time = new Duration();

        $this->assertEquals('13.3', $Time->fromString('13.27')->string('s.u', 1));
        $this->assertSame('0d 00:00:27.000', $Time->fromString('27')->string('z\d H:i:s.u', 3));
        $this->assertSame('0d 01:00:01', $Time->fromString('3601')->string('z\d H:i:s'));
        $this->assertSame('00:01', $Time->fromString('3601')->string('i:s'));
    }

    public function testStaticMethod(): void
    {
        $this->assertSame('1d 09:13:41', Duration::format('33:13:41,4'));
    }

    public function testExactLimits(): void
    {
        $Time = new Duration();

        $this->assertSame('1d 00:00:00', $Time->fromString('24:00:00')->string());
        $this->assertSame('10:00:00', $Time->fromString('10:00:00')->string());
        $this->assertSame('1:00:00', $Time->fromString(' 1:00:00')->string());
        $this->assertSame('10:00', $Time->fromString('   10:00')->string());
        $this->assertSame('1:00', $Time->fromString('    1:00')->string());
    }

    public function testStrangeResults(): void
    {
        $Time = new Duration();

        $this->assertSame('1:21', $Time->fromSeconds(162 * 0.2 / 0.4)->string(Duration::FORMAT_AUTO, 1));
        $this->assertSame('1:26', $Time->fromSeconds(172 * 0.2 / 0.4)->string(Duration::FORMAT_AUTO, 1));
    }

    public function testCompetitionResults(): void
    {
        $Time = new Duration();

        $this->assertSame('8,18s', $Time->fromSeconds(8.18)->string(Duration::FORMAT_COMPETITION, 2));
        $this->assertSame('13,00s', $Time->fromSeconds(13.00)->string(Duration::FORMAT_COMPETITION, 2));
        $this->assertSame('1:03', $Time->fromSeconds(63.00)->string(Duration::FORMAT_COMPETITION, 2));
    }

    public function testHourFormat(): void
    {
        $Time = new Duration();

        $this->assertSame('24:00:00', $Time->fromSeconds(24 * 3600)->string(Duration::FORMAT_WITH_HOURS));
        $this->assertSame('27:13:08', $Time->fromSeconds(27 * 3600 + 13 * 60 + 8)->string(Duration::FORMAT_WITH_HOURS));
    }

    public function testEmptyTime(): void
    {
        $Time = new Duration(0);

        $this->assertSame('0:00', $Time->string(Duration::FORMAT_AUTO));
        $this->assertSame('0:00', $Time->string(Duration::FORMAT_COMPETITION));
        $this->assertSame('0:00:00', $Time->string(Duration::FORMAT_WITH_HOURS));
        $this->assertSame('0d 00:00:00', $Time->string(Duration::FORMAT_WITH_DAYS));
    }

    public function testCompareToReturnsEmptyStringWhenThisDurationIsZero(): void
    {
        $this->assertSame('', (new Duration(0))->compareTo(new Duration(100)));
    }

    public function testCompareToReturnsEmptyStringWhenOtherDurationIsZero(): void
    {
        $this->assertSame('', (new Duration(100))->compareTo(new Duration(0)));
    }

    #[DataProvider('provideCompareToCases')]
    public function testCompareToComputesSignedDifference(float $thisSeconds, float $otherSeconds, bool $invert, string $expected): void
    {
        $this->assertSame(
            $expected,
            (new Duration($thisSeconds))->compareTo(new Duration($otherSeconds), $invert, true)
        );
    }

    /**
     * @return array<string, float[]|bool[]|string[]>
     */
    public static function provideCompareToCases(): array
    {
        return [
            'this larger, no invert' => [130.0, 100.0, false, '+0:30'],
            'this smaller, no invert' => [100.0, 130.0, false, '-0:30'],
            'this larger, inverted' => [130.0, 100.0, true, '-0:30'],
            'this smaller, inverted' => [100.0, 130.0, true, '+0:30'],
            'equal durations, no invert' => [100.0, 100.0, false, '-0:00'],
            'equal durations, inverted' => [100.0, 100.0, true, '+0:00'],
            'fractional difference rounds half away from zero' => [100.5, 100.0, false, '+0:01'],
        ];
    }

    public function testConstructorRejectsNonScalarInput(): void
    {
        $this->expectException(\TypeError::class);

        new Duration([1, 2, 3]);
    }

    public function testConstructorRejectsNull(): void
    {
        $this->expectException(\TypeError::class);

        new Duration(null);
    }

    public function testStaticFormatRejectsNonScalarInput(): void
    {
        $this->expectException(\TypeError::class);

        Duration::format([1, 2, 3]);
    }

    #[DataProvider('provideScalarInputTypes')]
    public function testConstructorAcceptsAllDocumentedScalarTypes(int|float|string $input, float $expectedSeconds): void
    {
        $this->assertSame($expectedSeconds, (new Duration($input))->seconds());
    }

    /**
     * @return array<string, 90.5[]|string[]|int[]|float[]>
     */
    public static function provideScalarInputTypes(): array
    {
        return [
            'int seconds' => [90, 90.0],
            'float seconds' => [90.5, 90.5],
            'numeric string seconds' => ['90', 90.0],
            'colon-formatted string' => ['1:30', 90.0],
        ];
    }
}
