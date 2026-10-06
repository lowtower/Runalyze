<?php

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use PHPUnit\Framework\MockObject\MockObject;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\StrategyCollection;
use Runalyze\Service\WeatherForecast\Strategy\StrategyInterface;

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
        $this->assertNull($this->Collection->tryToLoadForecast(new Location()));
        $this->assertNull($this->Collection->getLastSuccessfulStrategy());
    }

    public function testOneValidStrategy(): void
    {
        $exampleData = new WeatherData();
        $exampleData->Temperature = 5;

        /** @var StrategyInterface&MockObject $mock */
        $mock = $this->createMock(StrategyInterface::class);
        $mock
            ->method('isPossible')
            ->willReturn(true);
        $mock
            ->method('loadForecast')
            ->willReturn($exampleData);

        $this->Collection->add($mock);

        $result = $this->Collection->tryToLoadForecast(new Location());

        $this->assertInstanceOf(WeatherData::class, $result);
        $this->assertEquals(5, $result->Temperature);
        $this->assertEquals($mock, $this->Collection->getLastSuccessfulStrategy());
    }
}
