<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ActivityPlotPrecisionTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityPlotPrecision $object;

    protected function setUp(): void
    {
        $this->object = new ActivityPlotPrecision();
    }

    public function testDefaultValueIsTwoHundredPoints(): void
    {
        $this->assertSame('200points', $this->object->value());
        $this->assertTrue($this->object->byPoints());
        $this->assertFalse($this->object->byDistance());
        $this->assertSame(200, $this->object->numberOfPoints());
    }

    public function testAssignsDistanceBasedOption(): void
    {
        $this->object->set('100m');

        $this->assertTrue($this->object->byDistance());
        $this->assertFalse($this->object->byPoints());
        $this->assertSame(100, $this->object->distanceStep());
    }

    public function testAssignsPointsBasedOption(): void
    {
        $this->object->set('200points');

        $this->assertFalse($this->object->byDistance());
        $this->assertTrue($this->object->byPoints());
        $this->assertSame(200, $this->object->numberOfPoints());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('distanceOptionProvider')]
    public function testAllDistanceOptionsReportCorrectStep(string $option, int $expectedStep): void
    {
        $this->object->set($option);

        $this->assertTrue($this->object->byDistance());
        $this->assertSame($expectedStep, $this->object->distanceStep());
    }

    /**
     * @return \Iterator<string, array<(int|string)>>
     */
    public static function distanceOptionProvider(): \Iterator
    {
        yield '50m' => ['50m', 50];
        yield '100m' => ['100m', 100];
        yield '200m' => ['200m', 200];
        yield '500m' => ['500m', 500];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pointsOptionProvider')]
    public function testAllPointsOptionsReportCorrectCount(string $option, int $expectedCount): void
    {
        $this->object->set($option);

        $this->assertTrue($this->object->byPoints());
        $this->assertSame($expectedCount, $this->object->numberOfPoints());
    }

    /**
     * @return \Iterator<string, array<(int|string)>>
     */
    public static function pointsOptionProvider(): \Iterator
    {
        yield '50points' => ['50points', 50];
        yield '100points' => ['100points', 100];
        yield '1000points' => ['1000points', 1000];
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set('50m');
        $this->object->set('unknown');

        $this->assertSame('200points', $this->object->value());
    }
}
