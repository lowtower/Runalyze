<?php

declare(strict_types=1);

namespace Runalyze\Tests\Sports\Performance\Model;

use Runalyze\Sports\Performance\Model\AbstractModel;
use Runalyze\Sports\Performance\Model\TsbModel;

final class TsbModelTest extends \PHPUnit\Framework\TestCase
{
    public function testSingleWorkout(): void
    {
        $Model = new TsbModel(
            [
                0 => 1000,
            ],
            42,
            7
        );
        $Model->calculate();

        $this->assertSame(47, $Model->fitnessAt(0));
        $this->assertSame(250, $Model->fatigueAt(0));
        $this->assertSame(-203, $Model->performanceAt(0));
        $this->assertEqualsWithDelta(7.0, $Model->restDaysAt(0), 0.5);
    }

    public function testSimpleExample(): void
    {
        $Model = new TsbModel([
            0 => 150,
            1 => 50,
            2 => 40,
            5 => 60,
            6 => 30,
            7 => 100,
        ], 42, 7);
        $Model->calculate();

        $this->assertSame(7, $Model->fitnessAt(0));
        $this->assertSame(38, $Model->fatigueAt(0));
        $this->assertSame(-31, $Model->performanceAt(0));

        $this->assertSame(9, $Model->fitnessAt(1));
        $this->assertSame(10, $Model->fitnessAt(2));
        $this->assertSame(10, $Model->fitnessAt(3));
        $this->assertSame(9, $Model->fitnessAt(4));
        $this->assertSame(12, $Model->fitnessAt(5));
        $this->assertSame(13, $Model->fitnessAt(6));
        $this->assertSame(17, $Model->fitnessAt(7));

        $this->assertSame(41, $Model->fatigueAt(1));
        $this->assertSame(40, $Model->fatigueAt(2));
        $this->assertSame(30, $Model->fatigueAt(3));
        $this->assertSame(23, $Model->fatigueAt(4));
        $this->assertSame(32, $Model->fatigueAt(5));
        $this->assertSame(32, $Model->fatigueAt(6));
        $this->assertSame(49, $Model->fatigueAt(7));
    }

    public function testSteadyState(): void
    {
        $Model = new TsbModel(array_fill(0, 101, 50), 42, 7);
        $Model->calculate();

        $this->assertSame(50, $Model->fitnessAt(100));
        $this->assertSame(50, $Model->fatigueAt(100));

        $this->assertEqualsWithDelta(50, $Model->fitnessAt(70), 2.5);
        $this->assertEqualsWithDelta(50, $Model->fatigueAt(14), 2.5);

        $this->assertEqualsWithDelta(50, $Model->fitnessAt(50), 5.0);
        $this->assertEqualsWithDelta(50, $Model->fatigueAt(10), 5.0);

        $this->assertEqualsWithDelta(50, $Model->fitnessAt(42), 7.5);
        $this->assertEqualsWithDelta(50, $Model->fatigueAt(7), 7.5);
    }

    public function testFutureFitness(): void
    {
        $Model = new TsbModel([
            0 => 1000,
        ], 42, 7);
        $Model->setRange(0, 100);
        $Model->calculate();

        $this->assertSame(44, $Model->fitnessAt(1));
        $this->assertSame(42, $Model->fitnessAt(2));
        $this->assertSame(37, $Model->fitnessAt(5));
        $this->assertSame(29, $Model->fitnessAt(10));
        $this->assertSame(23, $Model->fitnessAt(15));
        $this->assertSame(18, $Model->fitnessAt(20));
        $this->assertSame(11, $Model->fitnessAt(30));
        $this->assertSame(7, $Model->fitnessAt(40));
        $this->assertSame(4, $Model->fitnessAt(50));
        $this->assertSame(3, $Model->fitnessAt(60));
        $this->assertSame(2, $Model->fitnessAt(70));
        $this->assertSame(1, $Model->fitnessAt(80));
        $this->assertSame(1, $Model->fitnessAt(90));
        $this->assertSame(0, $Model->fitnessAt(100));
    }

    public function testFutureFatigue(): void
    {
        $Model = new TsbModel([
            0 => 1000,
        ], 42, 7);
        $Model->setRange(0, 30);
        $Model->calculate();

        $this->assertSame(188, $Model->fatigueAt(1));
        $this->assertSame(141, $Model->fatigueAt(2));
        $this->assertSame(59, $Model->fatigueAt(5));
        $this->assertSame(14, $Model->fatigueAt(10));
        $this->assertSame(3, $Model->fatigueAt(15));
        $this->assertSame(1, $Model->fatigueAt(20));
        $this->assertSame(0, $Model->fatigueAt(22));
    }

    public function testBadTimeConstants(): void
    {
        $Model = new TsbModel([1000, 500], 14, 14);
        $Model->setRange(0, 10);
        $Model->calculate();

        $this->assertEquals(0, $Model->restDays(50, 100));
        $this->assertEquals(0, $Model->maxTrimpToBalanced(50, 100));
    }

    public function testExtremeValuesAtBeginning(): void
    {
        $model = new TsbModel([100, 100, 100, 100, 100, 100, 100, 0, 0, 0, 0, 0, 0, 0], 42, 7);
        $model->calculate();

        $this->assertEquals(
            [
                AbstractModel::FITNESS => [5, 9, 13, 17, 21, 25, 28, 27, 26, 25, 23, 22, 21, 20],
                AbstractModel::FATIGUE => [25, 44, 58, 68, 76, 82, 87, 65, 49, 37, 27, 21, 15, 12],
                AbstractModel::PERFORMANCE => [-20, -35, -44, -51, -55, -57, -58, -38, -23, -12, -4, 2, 6, 9],
            ],
            array_map(fn (array $a): array => array_map(fn ($v): int => (int) round($v), $a), $model->getArrays())
        );

        $restDays = [];
        for ($i = 0; $i < 14; ++$i) {
            $restDays[] = round($model->restDaysAt($i), 1);
        }

        $this->assertEquals([
            6.7, 6.6, 6.2, 5.8, 5.4, 4.9, 4.7, 3.7, 2.6, 1.6, 0.7, 0.0, 0.0, 0.0,
        ], $restDays);
    }
}
