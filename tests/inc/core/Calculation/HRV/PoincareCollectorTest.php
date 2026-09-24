<?php

declare(strict_types=1);

namespace Runalyze\Calculation\HRV;

use Runalyze\Model\HRV\Entity;

class PoincareCollectorTest extends \PHPUnit\Framework\TestCase
{
    public function testPairsWithinFilterAreCollected(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 550, 600],
            ])
        );

        $this->assertSame(
            [
                '500' => 550,
                '550' => 600,
            ],
            $collector->data()
        );
    }

    public function testPairsExceedingFilterAreExcluded(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 800],
            ]),
            200
        );

        $this->assertSame([], $collector->data());
    }

    public function testDifferenceExactlyAtFilterIsExcluded(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 700],
            ]),
            200
        );

        $this->assertSame([], $collector->data());
    }

    public function testDifferenceJustBelowFilterIsIncluded(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 699],
            ]),
            200
        );

        $this->assertSame(
            [
                '500' => 699,
            ],
            $collector->data()
        );
    }

    public function testCustomFilterIsRespected(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 530],
            ]),
            20
        );

        $this->assertSame([], $collector->data());
    }

    public function testSingleValueProducesEmptyPlotData(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500],
            ])
        );

        $this->assertSame([], $collector->data());
    }

    public function testEmptyDataProducesEmptyPlotData(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [],
            ])
        );

        $this->assertSame([], $collector->data());
    }

    public function testNegativeDifferenceIsHandledBySameFilter(): void
    {
        $collectorDescending = new PoincareCollector(
            new Entity([
                Entity::DATA => [600, 550],
            ]),
            100
        );

        $collectorAscending = new PoincareCollector(
            new Entity([
                Entity::DATA => [550, 600],
            ]),
            100
        );

        $this->assertSame(
            [
                '600' => 550,
            ],
            $collectorDescending->data()
        );

        $this->assertSame(
            [
                '550' => 600,
            ],
            $collectorAscending->data()
        );
    }

    public function testPairsContainAllDataPointsWithoutDeduplication(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 510, 500, 520],
            ])
        );

        $this->assertSame(
            [
                [500, 510],
                [510, 500],
                [500, 520],
            ],
            $collector->pairs()
        );
    }

    /**
     * Known limitation: the shared Plot class (Plot::convertData()) expects
     * an associative array in the form [x => y], because it converts this
     * generic format into Flot-compatible coordinate pairs for ~27 different
     * activity plots (Pace, Heartrate, Elevation, etc.).
     *
     * Since PHP array keys must be unique, RR-interval pairs that share the
     * same preceding value (x) overwrite each other here. This is most
     * relevant for this collector specifically, because unlike time- or
     * distance-indexed plots, RR intervals frequently repeat identical
     * values.
     *
     * @see \Plot::convertData()
     * @see \Runalyze\View\Activity\Plot\HRVPoincare
     */
    public function testDuplicatePrecedingValuesOverwriteEachOtherDueToPlotArchitecture(): void
    {
        $collector = new PoincareCollector(
            new Entity([
                Entity::DATA => [500, 510, 500, 520],
            ])
        );

        $this->assertSame(
            [
                '500' => 520,
                '510' => 500,
            ],
            $collector->data()
        );
    }
}
