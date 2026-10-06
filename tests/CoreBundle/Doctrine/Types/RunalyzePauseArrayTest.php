<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Runalyze\Bundle\CoreBundle\Doctrine\Types\RunalyzePauseArray;
use Runalyze\Parser\Activity\Common\Data\Pause\Pause;
use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;

class RunalyzePauseArrayTest extends \PHPUnit\Framework\TestCase
{
    protected RunalyzePauseArray $Type;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected \PHPUnit\Framework\MockObject\Stub $PlatformMock;

    protected function setUp(): void
    {
        /** @var RunalyzePauseArray $type */
        $type = RunalyzePauseArray::getType(RunalyzePauseArray::RUNALYZE_PAUSE_ARRAY);
        $this->Type = $type;
        $this->PlatformMock = $this->createStub(AbstractPlatform::class);
    }

    public function testEmptyData(): void
    {
        $this->assertNull($this->Type->convertToDatabaseValue(null, $this->PlatformMock));
        $this->assertNull($this->Type->convertToDatabaseValue('', $this->PlatformMock));
        $this->assertNull($this->Type->convertToDatabaseValue(new PauseCollection(), $this->PlatformMock));

        $nullResult = $this->Type->convertToPHPValue(null, $this->PlatformMock);
        $this->assertInstanceOf(PauseCollection::class, $nullResult);
        $this->assertTrue($nullResult->isEmpty());

        $emptyResult = $this->Type->convertToPHPValue('', $this->PlatformMock);
        $this->assertInstanceOf(PauseCollection::class, $emptyResult);
        $this->assertTrue($emptyResult->isEmpty());
    }

    public function testSimpleData(): void
    {
        $collection = new PauseCollection([
            new Pause(123, 50),
            (new Pause(234, 10))->setHeartRateDetails(160, 140),
            (new Pause(345, 120))->setRecoveryDetails(120, 90),
            (new Pause(456, 63))->setPerformanceCondition(-4),
        ]);

        $newCollection = $this->Type->convertToPHPValue($this->Type->convertToDatabaseValue($collection, $this->PlatformMock), $this->PlatformMock);

        $this->assertCount(4, $newCollection);
        $this->assertEquals(123, $newCollection[0]->getTimeIndex());
        $this->assertEquals(50, $newCollection[0]->getDuration());
        $this->assertEquals(160, $newCollection[1]->getHeartRateAtStart());
        $this->assertEquals(140, $newCollection[1]->getHeartRateAtEnd());
        $this->assertEquals(120, $newCollection[2]->getHeartRateAtRecovery());
        $this->assertEquals(90, $newCollection[2]->getTimeUntilRecovery());
        $this->assertEquals(-4, $newCollection[3]->getPerformanceCondition());
    }

    public function testLegacyExample(): void
    {
        $collection = $this->Type->convertToPHPValue(
            '[{"time":93,"duration":46,"hr-start":136,"hr-end":91},' .
            '{"time":339,"duration":49,"hr-start":150,"hr-end":109},' .
            '{"time":621,"duration":9,"hr-start":142,"hr-end":140}]',
            $this->PlatformMock
        );

        $this->assertCount(3, $collection);
        $this->assertSame([93, 46.0, 136, 91], [$collection[0]->getTimeIndex(), $collection[0]->getDuration(), $collection[0]->getHeartRateAtStart(), $collection[0]->getHeartRateAtEnd()]);
        $this->assertSame([339, 49.0, 150, 109], [$collection[1]->getTimeIndex(), $collection[1]->getDuration(), $collection[1]->getHeartRateAtStart(), $collection[1]->getHeartRateAtEnd()]);
        $this->assertSame([621, 9.0, 142, 140], [$collection[2]->getTimeIndex(), $collection[2]->getDuration(), $collection[2]->getHeartRateAtStart(), $collection[2]->getHeartRateAtEnd()]);
    }

    public function testBrokenExample(): void
    {
        $collection = $this->Type->convertToPHPValue(
            '[{"time":93,"duration":46,"hr-start":136,"hr-end":91},' .
            '{"time":339,"duration":49,"hr-start":150,"hr-end":109},' .
            '{"time":621,"duration":9,"hr-sta',
            $this->PlatformMock
        );

        $this->assertTrue($collection->isEmpty());
    }
}
