<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzeRoundArray;
use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

final class RunalyzeRoundArrayTest extends \PHPUnit\Framework\TestCase
{
    protected RunalyzeRoundArray $Type;

    protected function setUp(): void
    {
        /** @var RunalyzeRoundArray $type */
        $type = RunalyzeRoundArray::getType(RunalyzeRoundArray::RUNALYZE_ROUND_ARRAY);
        $this->Type = $type;
    }

    public function testEmptyData(): void
    {
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $this->assertNull($this->Type->convertToDatabaseValue(null, $PlatformMockStub));
        $this->assertNull($this->Type->convertToDatabaseValue('', $PlatformMockStub));
        $this->assertNull($this->Type->convertToDatabaseValue(new RoundCollection(), $PlatformMockStub));

        $nullResult = $this->Type->convertToPHPValue(null, $PlatformMockStub);
        $this->assertInstanceOf(RoundCollection::class, $nullResult);
        $this->assertTrue($nullResult->isEmpty());

        $emptyResult = $this->Type->convertToPHPValue('', $PlatformMockStub);
        $this->assertInstanceOf(RoundCollection::class, $emptyResult);
        $this->assertTrue($emptyResult->isEmpty());
    }

    public function testSimpleData(): void
    {
        $collection = new RoundCollection([
            new Round(1.0, 311, false),
            new Round(1.0, 189, true),
            new Round(1.0, 267, false),
        ]);
        $PlatformMockStub = $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class);

        $newCollection = $this->Type->convertToPHPValue($this->Type->convertToDatabaseValue($collection, $PlatformMockStub), $PlatformMockStub);

        $this->assertCount(3, $newCollection);
        $this->assertEqualsWithDelta(1.0, $newCollection[0]->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertEquals(311, $newCollection[0]->getDuration());
        $this->assertFalse($newCollection[0]->isActive());
        $this->assertEqualsWithDelta(1.0, $newCollection[1]->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertEquals(189, $newCollection[1]->getDuration());
        $this->assertTrue($newCollection[1]->isActive());
        $this->assertEqualsWithDelta(1.0, $newCollection[2]->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertEquals(267, $newCollection[2]->getDuration());
        $this->assertFalse($newCollection[2]->isActive());
    }

    public function testLegacyExample(): void
    {
        $collection = $this->Type->convertToPHPValue(
            'R1.200|5:20-0.400|1:20-1.000|3:17',
            $this->createStub(\Doctrine\DBAL\Platforms\AbstractPlatform::class)
        );

        $this->assertCount(3, $collection);
        $this->assertEquals([1.2, 320, false], [$collection[0]->getDistance(), $collection[0]->getDuration(), $collection[0]->isActive()]);
        $this->assertEquals([0.4, 80, true], [$collection[1]->getDistance(), $collection[1]->getDuration(), $collection[1]->isActive()]);
        $this->assertEquals([1.0, 197, true], [$collection[2]->getDistance(), $collection[2]->getDuration(), $collection[2]->isActive()]);
    }
}
