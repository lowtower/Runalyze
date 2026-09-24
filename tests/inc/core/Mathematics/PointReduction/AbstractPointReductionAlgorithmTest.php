<?php

declare(strict_types=1);

namespace Runalyze\Tests\Mathematics\PointReduction;

use Runalyze\Mathematics\PointReduction\AbstractPointReductionAlgorithm;

class AbstractPointReductionAlgorithmTest extends \PHPUnit\Framework\TestCase
{
    public function testPerpendicularDistanceForPointsOutsideTheLinesXRegion(): void
    {
        $this->assertEqualsWithDelta(5.0, AbstractPointReductionAlgorithm::shortestDistance(
            -4.0,
            3.0,
            0.0,
            0.0,
            2.0,
            2.0
        ), 0.0001);
        $this->assertEqualsWithDelta(4.95, AbstractPointReductionAlgorithm::perpendicularDistance(
            -4.0,
            3.0,
            0.0,
            0.0,
            2.0,
            2.0
        ), 0.005);
    }

    public function testDistanceForPointsOnTheLine(): void
    {
        $this->assertEqualsWithDelta(0.0, AbstractPointReductionAlgorithm::shortestDistance(
            1.0,
            1.0,
            0.0,
            0.0,
            2.0,
            2.0
        ), 0.0001);
        $this->assertEqualsWithDelta(0.0, AbstractPointReductionAlgorithm::perpendicularDistance(
            1.0,
            1.0,
            0.0,
            0.0,
            2.0,
            2.0
        ), 0.0001);

        $this->assertEqualsWithDelta(0.0, AbstractPointReductionAlgorithm::shortestDistance(
            1.0,
            1.0,
            1.0,
            1.0,
            2.0,
            2.0
        ), 0.0001);
        $this->assertEqualsWithDelta(0.0, AbstractPointReductionAlgorithm::perpendicularDistance(
            1.0,
            1.0,
            1.0,
            1.0,
            2.0,
            2.0
        ), 0.0001);

        $this->assertEqualsWithDelta(0.0, AbstractPointReductionAlgorithm::shortestDistance(
            2.0,
            2.0,
            2.0,
            2.0,
            0.0,
            0.0
        ), 0.0001);
        $this->assertEqualsWithDelta(0.0, AbstractPointReductionAlgorithm::perpendicularDistance(
            2.0,
            2.0,
            2.0,
            2.0,
            0.0,
            0.0
        ), 0.0001);
    }

    public function testDistanceToHorizontalLine(): void
    {
        $this->assertEqualsWithDelta(2.3, AbstractPointReductionAlgorithm::shortestDistance(
            6.9,
            5.5,
            0.0,
            3.2,
            10.0,
            3.2
        ), 0.0001);
        $this->assertEqualsWithDelta(2.3, AbstractPointReductionAlgorithm::perpendicularDistance(
            6.9,
            5.5,
            0.0,
            3.2,
            10.0,
            3.2
        ), 0.0001);
    }

    public function testDistanceToSomeUnspecificLine(): void
    {
        [$line1x, $line1y, $line2x, $line2y] = [1.0, 1.0, 3.0, 2.0];

        $this->assertEqualsWithDelta(0.89, AbstractPointReductionAlgorithm::shortestDistance(
            1.0,
            2.0,
            $line1x,
            $line1y,
            $line2x,
            $line2y
        ), 0.005);
        $this->assertEqualsWithDelta(0.89, AbstractPointReductionAlgorithm::perpendicularDistance(
            1.0,
            2.0,
            $line1x,
            $line1y,
            $line2x,
            $line2y
        ), 0.005);

        $this->assertEqualsWithDelta(0.45, AbstractPointReductionAlgorithm::shortestDistance(
            2.0,
            2.0,
            $line1x,
            $line1y,
            $line2x,
            $line2y
        ), 0.005);
        $this->assertEqualsWithDelta(0.45, AbstractPointReductionAlgorithm::perpendicularDistance(
            2.0,
            2.0,
            $line1x,
            $line1y,
            $line2x,
            $line2y
        ), 0.005);
    }

    public function testShortestDistanceForCollapsedLine(): void
    {
        $distance = AbstractPointReductionAlgorithm::shortestDistance(
            3.0,
            4.0,
            0.0,
            0.0,
            0.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }

    public function testShortestDistanceUsesFirstPointWhenProjectionIsBeforeSegment(): void
    {
        $distance = AbstractPointReductionAlgorithm::shortestDistance(
            -5.0,
            0.0,
            0.0,
            0.0,
            10.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }

    public function testShortestDistanceUsesLastPointWhenProjectionIsBehindSegment(): void
    {
        $distance = AbstractPointReductionAlgorithm::shortestDistance(
            15.0,
            0.0,
            0.0,
            0.0,
            10.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }

    public function testShortestDistanceUsesProjectionWithinSegment(): void
    {
        $distance = AbstractPointReductionAlgorithm::shortestDistance(
            5.0,
            5.0,
            0.0,
            0.0,
            10.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }

    public function testShortestDistanceForProjectionOnFirstPoint(): void
    {
        $distance = AbstractPointReductionAlgorithm::shortestDistance(
            0.0,
            5.0,
            0.0,
            0.0,
            10.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }

    public function testShortestDistanceForProjectionOnLastPoint(): void
    {
        $distance = AbstractPointReductionAlgorithm::shortestDistance(
            10.0,
            5.0,
            0.0,
            0.0,
            10.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }

    public function testPerpendicularDistanceForVerticalLine(): void
    {
        $distance = AbstractPointReductionAlgorithm::perpendicularDistance(
            3.0,   // point x
            5.0,   // point y
            1.0,   // line start x
            0.0,   // line start y
            1.0,   // line end x
            10.0   // line end y
        );

        $this->assertSame(2.0, $distance);
    }

    public function testPerpendicularDistanceForHorizontalLine(): void
    {
        $distance = AbstractPointReductionAlgorithm::perpendicularDistance(
            5.0,
            5.0,
            0.0,
            0.0,
            10.0,
            0.0
        );

        $this->assertEqualsWithDelta(5.0, $distance, 0.0001);
    }
}
