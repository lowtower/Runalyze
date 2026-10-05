<?php

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\DatabaseCacheInterface;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\DatabaseCache;

class DatabaseCacheTest extends \PHPUnit\Framework\TestCase
{
    protected function getMockForResponse(?WeatherData $data = null): DatabaseCacheInterface&\PHPUnit\Framework\MockObject\MockObject
    {
        /** @var DatabaseCacheInterface&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->createMock(DatabaseCacheInterface::class);
        $mock->method('getCachedWeatherDataFor')
            ->willReturn($data);

        return $mock;
    }

    public function testEmptyResponse(): void
    {
        $cache = new DatabaseCache($this->getMockForResponse());

        $this->assertNull($cache->loadForecast(new Location()));
    }

    public function testSimpleResponse(): void
    {
        $data = new WeatherData();
        $data->Temperature = 17;
        $data->WindDirection = 314;
        $data->WindSpeed = 42;

        $cache = new DatabaseCache($this->getMockForResponse($data));

        $this->assertEquals($data, $cache->loadForecast(new Location()));
    }
}
