<?php

declare(strict_types=1);

namespace Runalyze\Tests\Service\ElevationCorrection\Strategy;

use PHPUnit\Framework\MockObject\Stub;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyCollection;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;

final class StrategyCollectionTest extends \PHPUnit\Framework\TestCase
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

        /** @var StrategyInterface&Stub $stub */
        $stub = $this->createStub(StrategyInterface::class);
        $stub
            ->method('isPossible')
            ->willReturn(true);
        $stub
            ->method('loadAltitudeData')
            ->willReturn($altitudes);

        $this->Collection->add($stub);

        $result = $this->Collection->loadAltitudeData([49.9, 49.8, 49.7], [7.7, 7.6, 7.5]);

        $this->assertEquals($altitudes, $result);
        $this->assertEquals($stub, $this->Collection->getLastSuccessfulStrategy());
    }
}
