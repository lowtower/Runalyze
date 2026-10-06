<?php

namespace Runalyze\Tests\Bundle\CoreBundle\Services\Import;

use GuzzleHttp\Psr7\Response;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Service\WeatherForecast\DatabaseCacheInterface;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\DatabaseCache;
use Runalyze\Service\WeatherForecast\Strategy\MeteostatNet;
use Runalyze\Service\WeatherForecast\Strategy\OpenWeatherMap;
use Runalyze\Tests\Service\HttpClientAwareTestCaseTrait;

class WeatherForecastTest extends \PHPUnit\Framework\TestCase
{
    use HttpClientAwareTestCaseTrait;

    /**
     * @return DatabaseCacheInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDatabaseCacheMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        /** @var DatabaseCacheInterface&\PHPUnit\Framework\MockObject\MockObject $mock */
        $mock = $this->createMock(DatabaseCacheInterface::class);
        $mock->method('getCachedWeatherDataFor')
            ->willReturn(null);

        return $mock;
    }

    protected function buildWeatherForecast(
        DatabaseCacheInterface $databaseCache,
        OpenWeatherMap $openWeatherMap
    ): WeatherForecast {
        return new WeatherForecast(
            new DatabaseCache($databaseCache),
            $openWeatherMap,
            new MeteostatNet('', null, new \GuzzleHttp\Client()),
            $databaseCache
        );
    }

    public function testResultWithNoUsableMeasurementsIsNotCached(): void
    {
        $openWeatherMap = new OpenWeatherMap('dummy-api-key', null, $this->getMockForResponses([
            new Response(200, [], '{"cod":200}'),
        ]));

        $databaseCache = $this->getDatabaseCacheMock();
        $databaseCache->expects($this->never())
            ->method('cacheWeatherData');

        $weatherForecast = $this->buildWeatherForecast($databaseCache, $openWeatherMap);

        $location = new Location();
        $location->setPosition(52.2655, 10.5518);
        $location->setDateTime(new \DateTime());

        $result = $weatherForecast->loadForecast($location);

        $this->assertInstanceOf(
            \Runalyze\Parser\Activity\Common\Data\WeatherData::class,
            $result,
            'loadForecast() should still return the (practically useless) '
            . 'WeatherData object - the point of this test is that it must '
            . 'not be CACHED, not that it should be discarded entirely.'
        );
        $this->assertTrue($result->hasNoUsableData());
    }

    public function testResultWithRealMeasurementsIsStillCached(): void
    {
        $openWeatherMap = new OpenWeatherMap('dummy-api-key', null, $this->getMockForResponses([
            new Response(200, [], '{
                "weather":[{"id":800,"main":"Clear","description":"clear sky","icon":"01d"}],
                "main":{"temp":290,"pressure":1013,"humidity":50},
                "wind":{"speed":2.0,"deg":90},
                "cod":200
            }'),
        ]));

        $databaseCache = $this->getDatabaseCacheMock();
        $databaseCache->expects($this->once())
            ->method('cacheWeatherData')
            ->with(
                $this->callback(fn ($data): bool => ! $data->hasNoUsableData()),
                $this->isInstanceOf(Location::class)
            );

        $weatherForecast = $this->buildWeatherForecast($databaseCache, $openWeatherMap);

        $location = new Location();
        $location->setPosition(52.2655, 10.5518);
        $location->setDateTime(new \DateTime());

        $result = $weatherForecast->loadForecast($location);

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $result);
        $this->assertFalse($result->hasNoUsableData());
    }

    public function testNoResultAtAllIsNeverCached(): void
    {
        $openWeatherMap = new OpenWeatherMap('dummy-api-key', null, $this->getMockForResponses([
            new Response(200, [], ''),
        ]));

        $databaseCache = $this->getDatabaseCacheMock();
        $databaseCache->expects($this->never())
            ->method('cacheWeatherData');

        $weatherForecast = $this->buildWeatherForecast($databaseCache, $openWeatherMap);

        $location = new Location();
        $location->setPosition(52.2655, 10.5518);
        $location->setDateTime(new \DateTime());

        $result = $weatherForecast->loadForecast($location);

        $this->assertNull($result);
    }
}
