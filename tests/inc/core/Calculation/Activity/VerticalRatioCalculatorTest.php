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
}
