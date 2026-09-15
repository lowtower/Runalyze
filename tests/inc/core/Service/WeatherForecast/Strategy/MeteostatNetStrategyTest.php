<?php

namespace Runalyze\Tests\Service\WeatherForecast\Strategy;

use Psr\Log\LoggerInterface;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;
use Runalyze\Service\WeatherForecast\Strategy\MeteostatNet;

class MeteostatNetStrategyTest extends \PHPUnit\Framework\TestCase
{
    protected \ReflectionMethod $GetUrlForMethod;

    protected \ReflectionMethod $GetDataElementOfTimeMethod;

    protected \ReflectionMethod $GetWeatherDataFromResultMethod;

    protected MeteostatNet $Strategy;

    protected function setUp(): void
    {
        /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->Strategy = new MeteostatNet('test-api-key', null, new \GuzzleHttp\Client(), $logger);

        $this->GetUrlForMethod = new \ReflectionMethod($this->Strategy, 'getUrlFor');
        $this->GetUrlForMethod->setAccessible(true);

        $this->GetDataElementOfTimeMethod = new \ReflectionMethod($this->Strategy, 'getDataElementOfTime');
        $this->GetDataElementOfTimeMethod->setAccessible(true);

        $this->GetWeatherDataFromResultMethod = new \ReflectionMethod($this->Strategy, 'getWeatherDataFromResult');
        $this->GetWeatherDataFromResultMethod->setAccessible(true);
    }

    public function testUrlCreation(): void
    {
        $location = new Location();
        $location->setPosition(52.4, 10.7);
        $location->setTimezone('Europe/Berlin');
        $location->setDateTime(new \DateTime('2020-10-01 15:30:00'));

        $url = $this->GetUrlForMethod->invokeArgs($this->Strategy, [$location]);

        $this->assertStringContainsString('lat=52.4', $url);
        $this->assertStringContainsString('lon=10.7', $url);
        $this->assertStringContainsString('start=2020-10-01', $url);
        $this->assertStringContainsString('end=2020-10-01', $url);
        $this->assertStringContainsString('tz=Europe/Berlin', $url);
    }

    public function testSelectsCorrectHour(): void
    {
        $data = [
            [
                'time' => '2020-10-01 10:00:00',
                'temp' => 10,
            ],
            [
                'time' => '2020-10-01 11:00:00',
                'temp' => 11,
            ],
            [
                'time' => '2020-10-01 12:00:00',
                'temp' => 12,
            ],
        ];
        $date = new \DateTime('2020-10-01 11:30:00');
        $result = $this->GetDataElementOfTimeMethod->invokeArgs($this->Strategy, [$date, $data]);

        $this->assertEquals(11, $result['temp']);
    }

    public function testWeatherDataConversion(): void
    {
        $current = [
            'temp' => 15.3,
            'pres' => 1012.4,
            'rhum' => 81,
            'wspd' => 12.3,
            'wdir' => 270,
            'coco' => 1,
        ];
        $weather = $this->GetWeatherDataFromResultMethod->invokeArgs($this->Strategy, [$current]);

        $this->assertEquals(15, $weather->Temperature);
        $this->assertEquals(1012, $weather->AirPressure);
        $this->assertEquals(81, $weather->Humidity);
        $this->assertEquals(12, $weather->WindSpeed);
        $this->assertEquals(270, $weather->WindDirection);
        $this->assertEquals(WeatherConditionProfile::SUNNY, $weather->InternalConditionId);
    }

    public function testUrlCreationWithoutDateTime(): void
    {
        $location = new Location();
        $location->setPosition(52.4, 10.7);
        $location->setTimezone('Europe/Berlin');

        $timezone = new \DateTimeZone('Europe/Berlin');
        $dateBefore = new \DateTime('now', $timezone);

        $url = $this->GetUrlForMethod->invokeArgs($this->Strategy, [$location]);
        $dateAfter = new \DateTime('now', $timezone);

        $this->assertStringContainsString('lat=52.4', $url);
        $this->assertStringContainsString('lon=10.7', $url);
        $this->assertStringContainsString('tz=Europe/Berlin', $url);

        $hasExpectedStartDate = str_contains($url, 'start=' . $dateBefore->format('Y-m-d')) || str_contains($url, 'start=' . $dateAfter->format('Y-m-d'));
        $hasExpectedEndDate = str_contains($url, 'end=' . $dateBefore->format('Y-m-d')) || str_contains($url, 'end=' . $dateAfter->format('Y-m-d'));

        $this->assertTrue($hasExpectedStartDate);
        $this->assertTrue($hasExpectedEndDate);
    }

    public function testNoMatchingTimestampReturnsEmptyArray(): void
    {
        $data = [
            [
                'time' => '2020-10-01 10:00:00',
            ],
            [
                'time' => '2020-10-01 11:00:00',
            ],
        ];

        $result = $this->GetDataElementOfTimeMethod->invokeArgs($this->Strategy, [new \DateTime('2020-10-01 09:00:00'), $data]);

        $this->assertSame([], $result);
    }

    public function testUnknownWeatherCondition(): void
    {
        $weather = $this->GetWeatherDataFromResultMethod->invokeArgs($this->Strategy, [[
            'coco' => 9999,
        ]]);

        $this->assertEquals(WeatherConditionProfile::UNKNOWN, $weather->InternalConditionId);
    }

    public function testTemperatureAndWindSpeedAreRounded(): void
    {
        $current = [
            'temp' => 15.6,
            'wspd' => 12.6,
        ];

        $weather = $this->GetWeatherDataFromResultMethod->invokeArgs($this->Strategy, [$current]);

        $this->assertEquals(16, $weather->Temperature);
        $this->assertEquals(13, $weather->WindSpeed);
    }

    public function testProxyIsPassedToHttpClient(): void
    {
        /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $location = new Location();
        $location->setPosition(52.4, 10.7);
        $location->setTimezone('Europe/Berlin');
        $location->setDateTime(new \DateTime('2020-10-01 15:30:00'));

        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'data' => [],
            ])),
        ]);

        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new \GuzzleHttp\Client([
            'handler' => $handlerStack,
        ]);

        $strategy = new MeteostatNet('api-key', 'http://proxy.example:8080', $client, $logger);

        // Call the public method that triggers the request.
        // Adapt this to the actual StrategyInterface method.
        $strategy->loadForecast($location);

        $this->assertCount(1, $container);
        $this->assertSame('http://proxy.example:8080', $container[0]['options']['proxy']);
    }

    public function testHandlesLocationWithoutDateTime(): void
    {
        $timezone = new \DateTimeZone('Europe/Berlin');
        $nowInLocationTimezone = new \DateTime('now', $timezone);

        $container = [];
        $history = \GuzzleHttp\Middleware::history($container);

        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'data' => [
                    [
                        'time' => $nowInLocationTimezone->format('Y-m-d H:i:s'),
                        'temp' => 12.0,
                    ],
                ],
            ])),
        ]);

        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new \GuzzleHttp\Client([
            'handler' => $handlerStack,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $strategy = new MeteostatNet('test-api-key', null, $client, $logger);

        $location = new Location();
        $location->setPosition(52.4, 10.7);
        $location->setTimezone('Europe/Berlin');

        $result = $strategy->loadForecast($location);

        $this->assertNotNull(
            $result,
            'loadForecast() must not crash and must return usable weather '
            . 'data for a Location without an explicitly set DateTime, '
            . 'using "now" in the Location\'s own timezone - not UTC or '
            . "the test runner's ambient default timezone."
        );
        $this->assertEquals(12, $result->Temperature);
    }

    public function testNetworkFailureIsCaughtAndRecordsException(): void
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', 'https://meteostat.p.rapidapi.com/point/hourly');
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Exception\RequestException('Connection refused', $request),
        ]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \GuzzleHttp\Client([
            'handler' => $handlerStack,
        ]);

        $logger = $this->createMock(LoggerInterface::class);
        $strategy = new MeteostatNet('test-api-key', null, $client, $logger);

        $location = new Location();
        $location->setPosition(52.4, 10.7);
        $location->setTimezone('Europe/Berlin');
        $location->setDateTime(new \DateTime('2020-10-01 15:30:00'));

        $result = $strategy->loadForecast($location);

        $this->assertNull($result);
        $this->assertInstanceOf(
            \GuzzleHttp\Exception\RequestException::class,
            $strategy->getLastException()
        );
    }
}
