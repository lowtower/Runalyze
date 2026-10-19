<?php

declare(strict_types=1);

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\DarkSky;
use Runalyze\Tests\Service\HttpClientAwareTestCaseTrait;

final class DarkSkyTest extends \PHPUnit\Framework\TestCase
{
    use HttpClientAwareTestCaseTrait;

    public function testEmptyResponse(): void
    {
        $location = new Location();
        $location->setPosition(54.347, 10.1125);

        $darkSky = new DarkSky('', $this->getMockForResponses([
            new Response(200, [], ''),
        ]));

        $result = $darkSky->loadForecast($location);

        $this->assertNull($result);
    }

    public function testSimpleResponse(): void
    {
        $darkSky = new DarkSky('', $this->getMockForResponses([
            new Response(200, [], '{
               "latitude":54.3470453,
               "longitude":10.1125363,
               "timezone":"Europe/Berlin",
               "offset":2,
               "currently":{
                    "time":1474027196,
                  "summary":"Partly Cloudy",
                  "icon":"partly-cloudy-day",
                  "precipIntensity":0,
                  "precipProbability":0,
                  "temperature":73.2,
                  "apparentTemperature":73.2,
                  "dewPoint":57.78,
                  "humidity":0.59,
                  "windSpeed":11.2,
                  "windBearing":109,
                  "cloudCover":0.38,
                  "pressure":1012.66,
                  "ozone":264.7
               }
		    }'),
        ]));

        $location = new Location();
        $location->setPosition(54.347, 10.1125);

        $result = $darkSky->loadForecast($location);

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $result);

        $this->assertSame(WeatherConditionProfile::CHANGEABLE, $result->InternalConditionId);
        $this->assertEqualsWithDelta(18.02, $result->WindSpeed, 0.01);
        $this->assertSame(109, $result->WindDirection);
        $this->assertSame(59, $result->Humidity);
        $this->assertEquals(1013, $result->AirPressure);
        $this->assertEqualsWithDelta(22.8, $result->Temperature, 0.1);
        $this->assertSame(WeatherSourceProfile::DARK_SKY, $result->Source);
    }

    public function testSuccessfulResponseIsNotDiscardedAsNull(): void
    {
        $darkSky = new DarkSky('', $this->getMockForResponses([
            new Response(200, [], '{"currently":{"temperature":73.2}}'),
        ]));

        $location = new Location();
        $location->setPosition(54.347, 10.1125);

        $result = $darkSky->loadForecast($location);

        $this->assertInstanceOf(
            \Runalyze\Parser\Activity\Common\Data\WeatherData::class,
            $result,
            'loadForecast() must return actual weather data for a valid, '
            . 'successful API response containing a "currently" key - not '
            . 'null, which would make this strategy permanently '
            . 'non-functional regardless of API key validity.'
        );
    }

    public function testValidJsonWithoutCurrentlyKeyReturnsNullAndRecordsException(): void
    {
        $darkSky = new DarkSky('', $this->getMockForResponses([
            new Response(200, [], '{"latitude":54.347,"longitude":10.1125}'),
        ]));

        $location = new Location();
        $location->setPosition(54.347, 10.1125);

        $result = $darkSky->loadForecast($location);

        $this->assertNull($result);
        $this->assertInstanceOf(
            \Throwable::class,
            $darkSky->getLastException(),
            'getLastException() should record a reason when a response '
            . 'parses as valid JSON but is missing the expected '
            . '"currently" key, so callers can distinguish this from a '
            . 'genuine network failure.'
        );
    }

    public function testNetworkFailureIsCaughtAndRecordsException(): void
    {
        $request = new Request('GET', 'https://api.darksky.net/forecast/test');
        $mock = new MockHandler([
            new RequestException('Connection refused', $request),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new \GuzzleHttp\Client([
            'handler' => $handlerStack,
        ]);

        $darkSky = new DarkSky('', $client);

        $location = new Location();
        $location->setPosition(54.347, 10.1125);

        $result = $darkSky->loadForecast($location);

        $this->assertNull($result);
        $this->assertInstanceOf(RequestException::class, $darkSky->getLastException());
    }
}
