<?php

namespace Runalyze\Calculation\Activity;

use Runalyze\Model\Trackdata\Entity;
use Runalyze\Model\Trackdata\Loop;

class PaceSmootherTest extends \PHPUnit\Framework\TestCase
{
    public function testSimpleStepSmoothing(): void
    {
        $Smoother = new PaceSmoother(new Entity([
            Entity::TIME => [0, 7, 16, 20, 24, 25, 30, 40, 60, 66],
            Entity::DISTANCE => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]), false);

        $this->assertEquals([
            8, 4, 3, 15, // , 6 - the last one will be ignored for MODE_STEP
        ], $Smoother->smooth(2, PaceSmoother::MODE_STEP));
    }

    public function testSmoothingByDistance(): void
    {
        $Smoother = new PaceSmoother(new Entity([
            Entity::TIME => [0, 7, 16, 20, 24, 25, 30, 40, 60, 66],
            Entity::DISTANCE => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]), false);

        $this->assertEquals([
            8, 4, 3, 15, 6,
        ], $Smoother->smooth(2, PaceSmoother::MODE_DISTANCE));
    }

    public function testSmoothingByTime(): void
    {
        $Smoother = new PaceSmoother(new Entity([
            Entity::TIME => [0, 7, 16, 20, 24, 25, 30, 40, 60, 66],
            Entity::DISTANCE => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]), false);

        $this->assertEquals([
            round(20 / 3), 20 / 4, 20, 6,
        ], $Smoother->smooth(18, PaceSmoother::MODE_TIME));
    }

    public function testKeepingArraySize(): void
    {
        $arraySizes = range(15, 35);
        $stepSizes = range(2, 11);
        $modes = [
            'step' => PaceSmoother::MODE_STEP,
            'time' => PaceSmoother::MODE_TIME,
            'distance' => PaceSmoother::MODE_DISTANCE,
        ];

        foreach ($arraySizes as $arraySize) {
            $Smoother = new PaceSmoother(new Entity([
                Entity::TIME => range(0, $arraySize - 1),
                Entity::DISTANCE => range(0, $arraySize - 1),
            ]), true);

            foreach ($stepSizes as $stepSize) {
                foreach ($modes as $name => $mode) {
                    $this->assertCount(
                        $arraySize,
                        $Smoother->smooth($stepSize, $mode),
                        'Wrong array size for mode = ' . $name . ', arraySize = ' . $arraySize . ' and stepSize = ' . $stepSize
                    );
                }
            }
        }
    }

    public function testCurrentKeepsFloatingPointDistance(): void
    {
        $trackdata = new Entity([
            Entity::TIME => [
                30.0,
                60.0,
            ],
            Entity::DISTANCE => [
                0.1,
                0.2,
            ],
        ]);

        $loop = new Loop($trackdata);

        self::assertSame(0.1, $loop->current(
            Entity::DISTANCE
        ));
    }

    public function testMoveByFractionalDistanceAdvancesLoop(): void
    {
        $trackdata = new Entity([
            Entity::TIME => [
                30.0,
                60.0,
                90.0,
            ],
            Entity::DISTANCE => [
                0.1,
                0.2,
                0.3,
            ],
        ]);

        $loop = new Loop($trackdata);

        self::assertSame(0, $loop->index());

        $loop->move(
            Entity::DISTANCE,
            0.001
        );

        self::assertSame(1, $loop->index());
        self::assertSame(0, $loop->lastIndex());
    }

    public function testStepSizeMustBeGreaterThanZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Step size must be greater than zero.'
        );

        $smoother = new PaceSmoother(
            new Entity([
                Entity::TIME => [0, 60],
                Entity::DISTANCE => [0, 1],
            ])
        );

        $smoother->smooth(0);
    }

    public function testStepSizeMustBeIntegerInStepMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Step size must be an integer in step mode.'
        );

        $smoother = new PaceSmoother(
            new Entity([
                Entity::TIME => [0, 60],
                Entity::DISTANCE => [0, 1],
            ])
        );

        $smoother->smooth(
            1.5,
            PaceSmoother::MODE_STEP
        );
    }
}
