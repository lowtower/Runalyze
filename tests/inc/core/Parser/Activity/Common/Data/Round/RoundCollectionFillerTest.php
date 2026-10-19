<?php

declare(strict_types=1);

namespace Runalyze\Tests\Parser\Activity\Common\Data\Round;

use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollectionFiller;

final class RoundCollectionFillerTest extends \PHPUnit\Framework\TestCase
{
    public function testEasyCompletionByDistanceArray(): void
    {
        $collection = new RoundCollection([
            new Round(0.0, 100),
            new Round(0.0, 120),
        ]);

        $filler = new RoundCollectionFiller($collection);
        $filler->fillDistancesFromArray(
            [0, 50, 100, 150, 200, 220],
            [0.0, 0.2, 0.4, 0.6, 0.8, 0.9]
        );

        $this->assertEqualsWithDelta(0.9, $collection->getTotalDistance(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.4, $collection[0]->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.5, $collection[1]->getDistance(), PHP_FLOAT_EPSILON);
    }

    public function testEasyCompletionByTimeArray(): void
    {
        $collection = new RoundCollection([
            new Round(0.4, 0),
            new Round(0.5, 0),
        ]);

        $filler = new RoundCollectionFiller($collection);
        $filler->fillTimesFromArray(
            [0, 50, 100, 150, 200, 220],
            [0.0, 0.2, 0.4, 0.6, 0.8, 0.9]
        );

        $this->assertEquals(220, $collection->getTotalDuration());
        $this->assertEquals(100, $collection[0]->getDuration());
        $this->assertEquals(120, $collection[1]->getDuration());
    }

    public function testThatCompletionWithEmptyCollectionDoesNotFail(): void
    {
        $collection = new RoundCollection();

        $filler = new RoundCollectionFiller($collection);
        $filler->fillDistancesFromArray([1, 2, 3], [0.01, 0.02, 0.03]);
        $filler->fillTimesFromArray([1, 2, 3], [0.01, 0.02, 0.03]);

        $this->assertCount(0, $collection, 'Empty collection should remain empty after filling with empty data');
    }

    public function testWithTooShortDistanceArray(): void
    {
        $collection = new RoundCollection([
            new Round(0.4, 0),
            new Round(0.4, 0),
        ]);

        $filler = new RoundCollectionFiller($collection);
        $filler->fillTimesFromArray(
            [30, 60, 90],
            [0.2, 0.4, 0.6]
        );

        $this->assertEquals(60, $collection[0]->getDuration());
    }
}
