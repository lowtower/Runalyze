<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityPlotPrecisionTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityPlotPrecision $object;

    protected function setUp(): void
    {
        $this->object = new ActivityPlotPrecision();
    }

    public function testDefaultValueIsTwoHundredPoints(): void
    {
        self::assertSame('200points', $this->object->value());
        self::assertTrue($this->object->byPoints());
        self::assertFalse($this->object->byDistance());
        self::assertSame(200, $this->object->numberOfPoints());
    }

    public function testAssignsDistanceBasedOption(): void
    {
        $this->object->set('100m');

        self::assertTrue($this->object->byDistance());
        self::assertFalse($this->object->byPoints());
        self::assertSame(100, $this->object->distanceStep());
    }

    public function testAssignsPointsBasedOption(): void
    {
        $this->object->set('200points');

        self::assertFalse($this->object->byDistance());
        self::assertTrue($this->object->byPoints());
        self::assertSame(200, $this->object->numberOfPoints());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('distanceOptionProvider')]
    public function testAllDistanceOptionsReportCorrectStep(string $option, int $expectedStep): void
    {
        $this->object->set($option);

        self::assertTrue($this->object->byDistance());
        self::assertSame($expectedStep, $this->object->distanceStep());
    }

    /**
     * @return array<string, string[]|int[]>
     */
    public static function distanceOptionProvider(): array
    {
        return [
            '50m' => ['50m', 50],
            '100m' => ['100m', 100],
            '200m' => ['200m', 200],
            '500m' => ['500m', 500],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pointsOptionProvider')]
    public function testAllPointsOptionsReportCorrectCount(string $option, int $expectedCount): void
    {
        $this->object->set($option);

        self::assertTrue($this->object->byPoints());
        self::assertSame($expectedCount, $this->object->numberOfPoints());
    }

    /**
     * @return array<string, string[]|int[]>
     */
    public static function pointsOptionProvider(): array
    {
        return [
            '50points' => ['50points', 50],
            '100points' => ['100points', 100],
            '1000points' => ['1000points', 1000],
        ];
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set('50m');
        $this->object->set('unknown');

        self::assertSame('200points', $this->object->value());
    }
}
