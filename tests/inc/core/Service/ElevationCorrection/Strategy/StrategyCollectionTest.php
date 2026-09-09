<?php

namespace Runalyze\Tests\Service\ElevationCorrection\Strategy;

use Runalyze\Service\ElevationCorrection\Strategy\StrategyCollection;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

class StrategyCollectionTest extends \PHPUnit\Framework\TestCase
{
    protected StrategyCollection $Collection;

    protected function setUp(): void
    {
        $this->Collection = new StrategyCollection();
    }

    public function testEmptyCollection()
    {
        $this->assertNull($this->Collection->getLastSuccessfulStrategy());
        $this->assertNull($this->Collection->loadAltitudeData([], []));
        $this->assertNull($this->Collection->getLastSuccessfulStrategy());
    }

    public function testNonMatchingArraySizes()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->Collection->loadAltitudeData([49.9, 49.8], [7.7]);
    }

    public function testOneValidStrategy()
    {
        $altitudes = [123, 121, 115];

        /** @var StrategyInterface&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->getMockForAbstractClass(StrategyInterface::class);
        $mock->expects($this->any())
            ->method('isPossible')
            ->willReturn(true);
        $mock->expects($this->any())
            ->method('loadAltitudeData')
            ->willReturn($altitudes);

        $this->Collection->add($mock);

        $result = $this->Collection->loadAltitudeData([49.9, 49.8, 49.7], [7.7, 7.6, 7.5]);

        $this->assertEquals($altitudes, $result);
        $this->assertEquals($mock, $this->Collection->getLastSuccessfulStrategy());
    }
}
