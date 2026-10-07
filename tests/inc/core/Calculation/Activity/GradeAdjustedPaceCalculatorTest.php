<?php

declare(strict_types=1);

namespace Runalyze\Tests\Calculation\Activity;

use Runalyze\Calculation\Activity\GradeAdjustedPaceCalculator;
use Runalyze\Model\Route;
use Runalyze\Model\Trackdata;
use Runalyze\View\Activity\Context;

class GradeAdjustedPaceCalculatorTest extends \PHPUnit\Framework\TestCase
{
    public function testAdjustedTimeEqualsElapsedTimeForFlatCourse(): void
    {
        $calculator = $this->getCalculator(
            [0, 60, 120, 180],
            [0, 1, 2, 3],
            [0, 0, 0, 0]
        );

        $this->assertEqualsWithDelta(
            180.0,
            $calculator->getAdjustedTime(),
            0.01
        );
    }

    public function testAdjustedTimeIsLowerForUphillCourse(): void
    {
        $calculator = $this->getCalculator(
            [0, 60, 120, 180],
            [0, 1, 2, 3],
            [10, 10, 10, 10]
        );

        $this->assertLessThan(
            180.0,
            $calculator->getAdjustedTime()
        );
    }

    public function testAdjustedTimeIsHigherForDownhillCourse(): void
    {
        $calculator = $this->getCalculator(
            [0, 60, 120, 180],
            [0, 1, 2, 3],
            [-10, -10, -10, -10]
        );

        $this->assertGreaterThan(
            180.0,
            $calculator->getAdjustedTime()
        );
    }

    public function testAdjustedTimesAreOrderedCorrectly(): void
    {
        $uphill = $this->getCalculator(
            [0, 60, 120, 180],
            [0, 1, 2, 3],
            [10, 10, 10, 10]
        );

        $flat = $this->getCalculator(
            [0, 60, 120, 180],
            [0, 1, 2, 3],
            [0, 0, 0, 0]
        );

        $downhill = $this->getCalculator(
            [0, 60, 120, 180],
            [0, 1, 2, 3],
            [-10, -10, -10, -10]
        );

        $this->assertLessThan(
            $flat->getAdjustedTime(),
            $uphill->getAdjustedTime()
        );

        $this->assertGreaterThan(
            $flat->getAdjustedTime(),
            $downhill->getAdjustedTime()
        );
    }

    public function testSingleTrackPointResultsInZeroAdjustedTime(): void
    {
        $calculator = $this->getCalculator(
            [0],
            [0],
            [0]
        );

        $this->assertSame(
            0.0,
            $calculator->getAdjustedTime()
        );
    }

    public function testAdjustedTimeIsCalculatedOnlyOnce(): void
    {
        $calculator = $this->getCalculator(
            [0, 60, 120],
            [0, 1, 2],
            [0, 0, 0]
        );

        $first = $calculator->getAdjustedTime();
        $second = $calculator->getAdjustedTime();

        $this->assertSame($first, $second);
    }

    /**
     * @param int[] $time
     * @param int[] $distance
     * @param int[] $gradient
     */
    private function getCalculator(
        array $time,
        array $distance,
        array $gradient
    ): GradeAdjustedPaceCalculator {
        $trackdata = new Trackdata\Entity([
            Trackdata\Entity::TIME => $time,
            Trackdata\Entity::DISTANCE => $distance,
        ]);

        $route = new Route\Entity([]);

        $context = $this->createStub(Context::class);
        $context->method('trackdata')
            ->willReturn($trackdata);
        $context->method('route')
            ->willReturn($route);

        return new class($context, $gradient) extends GradeAdjustedPaceCalculator {
            public function __construct(
                Context $context,
                private readonly array $gradient
            ) {
                parent::__construct($context);
            }

            protected function getGradientSeries(): array
            {
                return $this->gradient;
            }
        };
    }
}
