<?php

namespace Runalyze\Tests\Service\ElevationCorrection\Strategy;

use PHPUnit\Framework\MockObject\MockObject;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyCollection;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

class StrategyCollectionTest extends \PHPUnit\Framework\TestCase
{
    protected StrategyCollection $Collection;

    protected function setUp(): void
    {
        $this->Collection = new StrategyCollection();
    }

    public function testEmptyCollection(): void
    {
        $this->assertNull($this->Collection->getLastSuccessfulStrategy());
        $this->assertNull($this->Collection->loadAltitudeData([], []));
        $this->assertNull($this->Collection->getLastSuccessfulStrategy());
    }

    public function testNonMatchingArraySizes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->Collection->loadAltitudeData([49.9, 49.8], [7.7]);
    }

    public function testOneValidStrategy(): void
    {
        $altitudes = [123, 121, 115];

        /** @var StrategyInterface&MockObject $mock */
        $mock = $this->createMock(StrategyInterface::class);
        $mock
            ->method('isPossible')
            ->willReturn(true);
        $mock
            ->method('loadAltitudeData')
            ->willReturn($altitudes);

        $this->Collection->add($mock);

        $result = $this->Collection->loadAltitudeData([49.9, 49.8, 49.7], [7.7, 7.6, 7.5]);

        $this->assertEquals($altitudes, $result);
        $this->assertEquals($mock, $this->Collection->getLastSuccessfulStrategy());
    }
}
