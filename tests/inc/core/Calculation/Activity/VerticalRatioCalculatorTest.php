<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Activity;

use Runalyze\Model\Activity;
use Runalyze\Model\Trackdata;

class VerticalRatioCalculatorTest extends \PHPUnit\Framework\TestCase
{
    public function testForActivity(): void
    {
        $this->assertEquals(53.0, VerticalRatioCalculator::forActivity(new Activity\Entity([
            Activity\Entity::VERTICAL_OSCILLATION => 53,
            Activity\Entity::STRIDE_LENGTH => 100,
        ])));
    }

    public function testSingleValue(): void
    {
        $Calculator = new VerticalRatioCalculator(
            new Trackdata\Entity([
                Trackdata\Entity::VERTICAL_OSCILLATION => [100],
                Trackdata\Entity::STRIDE_LENGTH => [100],
            ])
        );
        $Calculator->calculate();

        $this->assertEquals(100, $Calculator->average());
    }

    public function testSimpleArray(): void
    {
        $Calculator = new VerticalRatioCalculator(
            new Trackdata\Entity([
                Trackdata\Entity::VERTICAL_OSCILLATION => [73, 80, 90, 100, 120],
                Trackdata\Entity::STRIDE_LENGTH => [100, 120, 150, 150, 150],
            ])
        );
        $Calculator->calculate();

        $this->assertEquals(69.0, $Calculator->average());
        $this->assertEquals(
            [73, 67, 60, 67, 80],
            $Calculator->verticalRatioData()
        );
    }

    public function testCalculateReturnsEmptyArrayForIncompleteTrackdata(): void
    {
        $calculator = new VerticalRatioCalculator(
            new Trackdata\Entity([
                Trackdata\Entity::STRIDE_LENGTH => [100],
            ])
        );

        $this->assertSame([], $calculator->calculate());
    }

    public function testVerticalRatioIsNullForNonNumericOscillation(): void
    {
        $calculator = new VerticalRatioCalculator(
            new Trackdata\Entity([
                Trackdata\Entity::VERTICAL_OSCILLATION => ['foo'],
                Trackdata\Entity::STRIDE_LENGTH => [100],
            ])
        );

        $calculator->calculate();

        $this->assertEquals(
            [null],
            $calculator->verticalRatioData()
        );
    }

    public function testAverageReturnsNullBeforeCalculation(): void
    {
        $calculator = new VerticalRatioCalculator(
            new Trackdata\Entity([])
        );

        $this->assertNull($calculator->average());
    }

    public function testAverageWithoutTimeArray(): void
    {
        $calculator = new VerticalRatioCalculator(
            new Trackdata\Entity([
                Trackdata\Entity::VERTICAL_OSCILLATION => [50, 100],
                Trackdata\Entity::STRIDE_LENGTH => [100, 100],
            ])
        );

        $calculator->calculate();

        $this->assertSame(
            75.0,
            $calculator->average()
        );
    }

    public function testForActivityReturnsNullForZeroOscillation(): void
    {
        $this->assertNull(
            VerticalRatioCalculator::forActivity(
                new Activity\Entity([
                    Activity\Entity::VERTICAL_OSCILLATION => 0,
                    Activity\Entity::STRIDE_LENGTH => 100,
                ])
            )
        );
    }
}
