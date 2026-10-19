<?php

declare(strict_types=1);

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use PHPUnit\Framework\MockObject\Stub;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\StrategyCollection;
use Runalyze\Service\WeatherForecast\Strategy\StrategyInterface;

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
        $this->assertNull($this->Collection->tryToLoadForecast(new Location()));
        $this->assertNull($this->Collection->getLastSuccessfulStrategy());
    }

    public function testOneValidStrategy(): void
    {
        $exampleData = new WeatherData();
        $exampleData->Temperature = 5;

        /** @var StrategyInterface&Stub $stub */
        $stub = $this->createStub(StrategyInterface::class);
        $stub
            ->method('isPossible')
            ->willReturn(true);
        $stub
            ->method('loadForecast')
            ->willReturn($exampleData);

        $this->Collection->add($stub);

        $result = $this->Collection->tryToLoadForecast(new Location());

        $this->assertInstanceOf(WeatherData::class, $result);
        $this->assertEquals(5, $result->Temperature);
        $this->assertEquals($stub, $this->Collection->getLastSuccessfulStrategy());
    }
}
