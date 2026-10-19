<?php

declare(strict_types=1);

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use GuzzleHttp\Psr7\Response;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\OpenWeatherMap;
use Runalyze\Tests\Service\HttpClientAwareTestCaseTrait;

final class OpenWeatherMapTest extends \PHPUnit\Framework\TestCase
{
    use HttpClientAwareTestCaseTrait;

    public function testEmptyResponse(): void
    {
        $darkSky = new OpenWeatherMap('', null, $this->getMockForResponses([
            new Response(200, [], ''),
        ]));

        $location = new Location();
        $location->setPosition(49.45, 7.75);
        $location->setDateTime(new \DateTime());

        $result = $darkSky->loadForecast($location);

        $this->assertNull($result);
    }

    public function testSimpleResponse(): void
    {
        $darkSky = new OpenWeatherMap('', null, $this->getMockForResponses([
            new Response(200, [], '{
				"coord":{"lon":7.75,"lat":49.45},
				"sys":{"message":0.0221,"country":"DE","sunrise":1401334144,"sunset":1401391431},
				"weather":[{"id":803,"main":"Clouds","description":"broken clouds","icon":"04d"}],
				"base":"cmc stations",
				"main":{"temp":290,"pressure":1013.456,"temp_min":16.11,"temp_max":18.2,"humidity":59.24},
				"wind":{"speed":1.76,"deg":313.5},
				"clouds":{"all":76},
				"dt":1401371226,
				"id":2894003,
				"name":"Kaiserslautern",
				"cod":200
			}'),
        ]));

        $location = new Location();
        $location->setPosition(49.45, 7.75);
        $location->setDateTime(new \DateTime());

        $result = $darkSky->loadForecast($location);

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $result);

        $this->assertSame(WeatherConditionProfile::CLOUDY, $result->InternalConditionId);
        $this->assertEqualsWithDelta(6.34, $result->WindSpeed, 0.01);
        $this->assertSame(314, $result->WindDirection);
        $this->assertSame(59, $result->Humidity);
        $this->assertEquals(1013, $result->AirPressure);
        $this->assertEqualsWithDelta(17.0, $result->Temperature, 0.1);
        $this->assertSame(WeatherSourceProfile::OPEN_WEATHER_MAP, $result->Source);
    }

    public function testWindDirectionOf360(): void
    {
        $darkSky = new OpenWeatherMap('', null, $this->getMockForResponses([
            new Response(200, [], '{
				"wind":{"speed":1.76,"deg":359.9}
			}'),
        ]));

        $location = new Location();
        $location->setPosition(49.45, 7.75);
        $location->setDateTime(new \DateTime());

        $result = $darkSky->loadForecast($location);

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $result);

        $this->assertSame(0, $result->WindDirection);
    }

    public function testTooOldActivityReturnsNullNotEmptyWeatherData(): void
    {
        $openWeatherMap = new OpenWeatherMap('some-api-key', null, $this->getMockForResponses([]));

        $location = new Location();
        $location->setPosition(49.45, 7.75);
        // one full day old - well past both the previous hardcoded 7200s
        // (2h) threshold and any reasonable configured value.
        $location->setDateTime(new \DateTime()->modify('-1 day'));

        $result = $openWeatherMap->loadForecast($location);

        $this->assertNull(
            $result,
            'loadForecast() must return null (not a WeatherData object with '
            . 'all-null fields) when the activity is too old for this '
            . 'strategy to even attempt a request - otherwise '
            . 'WeatherData::isEmpty() incorrectly reports success with no '
            . 'usable data instead of a proper failure.'
        );
    }

    public function testConfigurableMaxAgeAllowsOlderActivityToBeAttempted(): void
    {
        $oneDayInSeconds = 86400;

        $openWeatherMap = new OpenWeatherMap('some-api-key', null, $this->getMockForResponses([
            new Response(200, [], '{
				"weather":[{"id":800,"main":"Clear","description":"clear sky","icon":"01d"}],
				"main":{"temp":290,"pressure":1013,"humidity":50},
				"wind":{"speed":2.0,"deg":90},
				"cod":200
			}'),
        ]), null, $oneDayInSeconds);

        $location = new Location();
        $location->setPosition(49.45, 7.75);
        // 5 hours old: would have been rejected by the previous hardcoded
        // 7200s (2h) default, but is well within a 24h configured threshold.
        $location->setDateTime(new \DateTime()->modify('-5 hours'));

        $result = $openWeatherMap->loadForecast($location);

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $result);
        $this->assertSame(WeatherConditionProfile::SUNNY, $result->InternalConditionId);
    }

    public function testInvalidApiKeyResponseReturnsNullAndRecordsException(): void
    {
        $openWeatherMap = new OpenWeatherMap('invalid-key', null, $this->getMockForResponses([
            new Response(200, [], '{"cod":401,"message":"Invalid API key. Please see https://openweathermap.org/faq#error401 for more info."}'),
        ]));

        $location = new Location();
        $location->setPosition(49.45, 7.75);
        $location->setDateTime(new \DateTime());

        $result = $openWeatherMap->loadForecast($location);

        $this->assertNull($result);
        $this->assertInstanceOf(\Throwable::class, $openWeatherMap->getLastException());
        $this->assertStringContainsString('401', $openWeatherMap->getLastException()->getMessage());
    }
}
