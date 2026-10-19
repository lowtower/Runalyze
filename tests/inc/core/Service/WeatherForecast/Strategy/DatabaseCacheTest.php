<?php

declare(strict_types=1);

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use PHPUnit\Framework\MockObject\Stub;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\DatabaseCacheInterface;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\DatabaseCache;

final class DatabaseCacheTest extends \PHPUnit\Framework\TestCase
{
    protected function getStubForResponse(?WeatherData $data = null): DatabaseCacheInterface&Stub
    {
        $stub = $this->createStub(DatabaseCacheInterface::class);
        $stub->method('getCachedWeatherDataFor')
            ->willReturn($data);

        return $stub;
    }

    public function testEmptyResponse(): void
    {
        $cache = new DatabaseCache($this->getStubForResponse());

        $this->assertNull($cache->loadForecast(new Location()));
    }

    public function testSimpleResponse(): void
    {
        $data = new WeatherData();
        $data->Temperature = 17;
        $data->WindDirection = 314;
        $data->WindSpeed = 42;

        $cache = new DatabaseCache($this->getStubForResponse($data));

        $this->assertEquals($data, $cache->loadForecast(new Location()));
    }
}
