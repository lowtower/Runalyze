<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\AbstractLogger;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Service\RouteNameEvaluation\OsmCsvData;
use Runalyze\Service\RouteNameEvaluation\OsmCsvDataCollection;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalOsm;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult;

class RouteNameEvalOsmRecordingLogger extends AbstractLogger
{
    /**
     * @var array<int, array{level: mixed, message: string, context: array}>
     */
    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

/**
 * Test for RouteNameEvalOsm.
 */
#[\PHPUnit\Framework\Attributes\Group('default')]
class RouteNameEvalOsmTest extends \PHPUnit\Framework\TestCase
{
    private RouteNameEvalOsm $underTest;

    private MockObject&OsmCsvDataCollection $coll;

    protected function setUp(): void
    {
        $this->underTest = new RouteNameEvalOsm('', '', new Client());
        $this->coll = $this->createMock(OsmCsvDataCollection::class);
    }

    private function getSportRunning(): Sport
    {
        $s = new Sport();
        $s->setInternalSportId(SportProfile::RUNNING);
        return $s;
    }

    private function getSportMountain(): Sport
    {
        $s = new Sport();
        $s->setInternalSportId(SportProfile::MOUNTAINEERING);
        return $s;
    }

    private function createRouteWithCoordinates(int $pointCount, float $latStart = 47.0, float $lonStart = 11.0, float $step = 0.0005): Route
    {
        $lats = [];
        $lons = [];

        for ($i = 0; $i < $pointCount; $i++) {
            $lats[] = $latStart + $i * $step;
            $lons[] = $lonStart + $i * $step;
        }

        $route = new Route();
        $route->setLatitudesAndLongitudes($lats, $lons);
        $route->synchronizeMinMaxGeohashIfRequired();

        return $route;
    }

    /**
     * @param array<int, \Psr\Http\Message\ResponseInterface|\Throwable> $responses
     * @param array<int, array<string, mixed>> $historyContainer
     */
    private function createClientWithResponses(array $responses, array &$historyContainer = [], bool $httpErrors = true): Client
    {
        $historyContainer = [];
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($historyContainer));

        return new Client([
            'handler' => $stack,
            'proxy' => '',
            'http_errors' => $httpErrors,
        ]);
    }

    private function invokeRequest(RouteNameEvalOsm $service, ?int $routeId, string $payload): string
    {
        return (new \ReflectionMethod($service, 'request'))->invoke($service, $routeId, $payload);
    }

    private function invokeCreateOsmPayload(RouteNameEvalOsm $service, Route $route, float $distance): string
    {
        return (new \ReflectionMethod($service, 'createOsmPayload'))->invoke($service, $route, $distance);
    }

    public function testCreateResultAllExists(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, ['apeak1', 'apeak2']],
                    [OsmCsvData::$CAT_PLACE, ['aplace']],
                    [OsmCsvData::$CAT_HUT, ['ahut']],
                    [OsmCsvData::$CAT_WAY, ['away']],
                    [OsmCsvData::$CAT_INFO, ['ainfo']],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('apeak1 - apeak2 - aplace', $result->getNames());
        $this->assertEquals("Verpflegung: ahut\nWege: away\nInfo: ainfo\n", $result->getNotes());
    }

    public function testCreateResultOnlyPeak(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, ['apeak1']],
                    [OsmCsvData::$CAT_PLACE, []],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, []],
                    [OsmCsvData::$CAT_INFO, []],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('apeak1', $result->getNames());
        $this->assertEquals('', $result->getNotes());
    }

    public function testCreateResultPeakHutMissing(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, ['aplace1', 'aplace2']],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, ['away']],
                    [OsmCsvData::$CAT_INFO, ['ainfo']],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('aplace1 - aplace2', $result->getNames());
        $this->assertEquals("Wege: away\nInfo: ainfo\n", $result->getNotes());
    }

    public function testCreateResultOnlyPlace(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, ['aplace1', 'aplace2']],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, []],
                    [OsmCsvData::$CAT_INFO, []],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('aplace1 - aplace2', $result->getNames());
        $this->assertEquals('', $result->getNotes());
    }

    public function testCreateResultPeakPlaceMissing(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, []],
                    [OsmCsvData::$CAT_HUT, ['ahut1', 'ahut2']],
                    [OsmCsvData::$CAT_WAY, ['away1', 'away2']],
                    [OsmCsvData::$CAT_INFO, ['ainfo']],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('ahut1 - ahut2 - away1 - away2', $result->getNames());
        $this->assertEquals("Info: ainfo\n", $result->getNotes());
    }

    public function testCreateResultPeakPlaceHutMissing(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, []],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, ['away1', 'away2']],
                    [OsmCsvData::$CAT_INFO, ['ainfo']],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('away1 - away2', $result->getNames());
        $this->assertEquals("Info: ainfo\n", $result->getNotes());
    }

    public function testCreateResultPeakPlaceHutWayMissing(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, []],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, []],
                    [OsmCsvData::$CAT_INFO, ['ainfo1', 'ainfo2']],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('ainfo1 - ainfo2', $result->getNames());
        $this->assertEquals('', $result->getNotes());
    }

    public function testCreateResultAllMissing(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, []],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, []],
                    [OsmCsvData::$CAT_INFO, []],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertNull($result);
    }

    public function testCreateResultHutWalking(): void
    {
        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, ['aplace']],
                    [OsmCsvData::$CAT_HUT, ['ahut']],
                    [OsmCsvData::$CAT_WAY, []],
                    [OsmCsvData::$CAT_INFO, []],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals('aplace', $result->getNames());
        $this->assertEquals("Verpflegung: ahut\n", $result->getNotes());

        $result = $this->underTest->createResult($this->getSportMountain(), $this->coll);

        $this->assertEquals('aplace - ahut', $result->getNames());
        $this->assertEquals('', $result->getNotes());
    }

    public function testCreateResultLimits(): void
    {
        $a = [];
        for ($i = 0; $i < 8; $i++) {
            $a[] = str_repeat((string) $i, 32);
        }

        $this->coll->method('getNames')
            ->will($this->returnValueMap(
                [
                    [OsmCsvData::$CAT_PEAK, []],
                    [OsmCsvData::$CAT_PLACE, $a],
                    [OsmCsvData::$CAT_HUT, []],
                    [OsmCsvData::$CAT_WAY, []],
                    [OsmCsvData::$CAT_INFO, []],
                ]
            ));

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertEquals(implode(' - ', array_slice($a, 0, 7)), $result->getNames());
    }

    public function testEvaluateReturnsNullImmediatelyWhenUrlIsEmpty(): void
    {
        $client = $this->createClientWithResponses([]);
        $service = new RouteNameEvalOsm('', '', $client);
        $route = $this->createRouteWithCoordinates(5);

        $result = $service->evaluate($this->getSportRunning(), $route, 5.0);

        self::assertNull($result);
    }

    public function testEvaluateReturnsNullWhenHttpResponseBodyIsEmpty(): void
    {
        $history = [];
        $client = $this->createClientWithResponses([new Response(200, [], '')], $history);
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client);
        $route = $this->createRouteWithCoordinates(20);

        $result = $service->evaluate($this->getSportRunning(), $route, 5.0);

        self::assertNull($result);
        self::assertCount(1, $history, 'exactly one HTTP request should have been made');
    }

    public function testEvaluateThrowsAndLogsErrorForNonSuccessStatusCode(): void
    {
        $client = $this->createClientWithResponses([new Response(500, [], 'Internal Server Error')]);
        $logger = new RouteNameEvalOsmRecordingLogger();
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client, $logger);
        $route = $this->createRouteWithCoordinates(20);

        try {
            $service->evaluate($this->getSportRunning(), $route, 5.0);
            self::fail('Expected exception was not thrown.');
        } catch (\Exception $exception) {
            self::assertStringContainsString('Route evaluation result in error', $exception->getMessage());
        }

        $errors = array_values(array_filter($logger->records, static fn (array $r): bool => $r['level'] === 'error'));
        self::assertCount(1, $errors);
        self::assertStringContainsString('Overpass-API request failed.', $errors[0]['message']);
    }

    public function testEvaluateThrowsAndLogsErrorWhenHttpClientThrowsRequestException(): void
    {
        $requestException = new RequestException(
            'cURL error: Could not resolve host',
            new GuzzlePsr7Request('POST', 'http://example.test/api')
        );
        $client = $this->createClientWithResponses([$requestException]);
        $logger = new RouteNameEvalOsmRecordingLogger();
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client, $logger);
        $route = $this->createRouteWithCoordinates(20);

        try {
            $service->evaluate($this->getSportRunning(), $route, 5.0);
            self::fail('Expected exception was not thrown.');
        } catch (\Exception $exception) {
            self::assertStringContainsString('Route evaluation result in error', $exception->getMessage());
        }

        $errors = array_values(array_filter($logger->records, static fn (array $r): bool => $r['level'] === 'error'));
        self::assertCount(1, $errors);
        self::assertStringContainsString('Overpass-API request failed.', $errors[0]['message']);
        self::assertSame($requestException, $errors[0]['context']['exception']);
    }

    public function testEvaluateReturnsCombinedNameAndNotesOnSuccessfulResponse(): void
    {
        $pipeSeparatedCsv = '@type|name|name:de|place|traffic_sign|tourism|landuse|natural|ele|hiking|sac_scale|amenity' . PHP_EOL .
              'node|Kramerspitz||||||peak|1985|||' . PHP_EOL .
              'node|Königsstand||||||peak|1453|||' . PHP_EOL .
              'node|Wandergebiet Kramer||||information||||||' . PHP_EOL .
              'way|Kramersteig||||||||yes|hiking|' . PHP_EOL .
              'relation|See||||||water||||' . PHP_EOL .
              'way|Kellerleitensteig||||||||yes|hiking|' . PHP_EOL .
              'node|Garmisch||town||||||||' . PHP_EOL .
              'way|Kramersteig||||||||yes|hiking|' . PHP_EOL .
              'way|Kramerplateauweg||||||||yes|hiking|';

        $rawTabSeparatedCsv = str_replace('|', "\t", $pipeSeparatedCsv);

        $client = $this->createClientWithResponses([new Response(200, [], $rawTabSeparatedCsv)]);
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client);
        $route = $this->createRouteWithCoordinates(20);

        $result = $service->evaluate($this->getSportRunning(), $route, 5.0);

        self::assertInstanceOf(RouteNameEvalResult::class, $result);
        self::assertSame('Kramerspitz - Königsstand - Garmisch - See', $result->getNames());
        self::assertSame(
            "Wege: Kellerleitensteig, Kramerplateauweg, Kramersteig\nInfo: Wandergebiet Kramer\n",
            $result->getNotes()
        );
    }

    public function testRequestReplacesExistingPipesThenConvertsTabsToPipes(): void
    {
        $client = $this->createClientWithResponses([new Response(200, [], "a|b\tc\td")]);
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client);

        $result = $this->invokeRequest($service, 1, 'payload');

        self::assertSame('a/b|c|d', $result);
    }

    public function testRequestSendsPayloadAsRawBodyWithDataPrefix(): void
    {
        $history = [];
        $client = $this->createClientWithResponses([new Response(200, [], 'ok')], $history);
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client);

        $this->invokeRequest($service, 1, 'my-payload-content');

        self::assertSame('data=my-payload-content', (string) $history[0]['request']->getBody());
    }

    public function testRequestSendsUserAgentHeader(): void
    {
        $history = [];
        $client = $this->createClientWithResponses([new Response(200, [], 'ok')], $history);
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client);

        $this->invokeRequest($service, 1, 'payload');

        self::assertStringStartsWith('Runalyze/4.3.0 host-', $history[0]['request']->getHeaderLine('User-Agent'));
    }

    public function testRequestIncludesProxyOptionWhenProxyIsConfigured(): void
    {
        $history = [];
        $client = $this->createClientWithResponses([new Response(200, [], 'ok')], $history);
        $service = new RouteNameEvalOsm('http://example.test/api', '127.0.0.1:8080', $client);

        $this->invokeRequest($service, 1, 'payload');

        self::assertSame('127.0.0.1:8080', $history[0]['options']['proxy']);
    }

    public function testRequestOmitsProxyOptionWhenProxyIsEmpty(): void
    {
        $history = [];
        $client = $this->createClientWithResponses([new Response(200, [], 'ok')], $history);
        $service = new RouteNameEvalOsm('http://example.test/api', '', $client);

        $this->invokeRequest($service, 1, 'payload');

        self::assertSame('', $history[0]['options']['proxy']);
    }

    // --- createOsmPayload() (invoked directly via reflection) -------------------------------

    public function testCreateOsmPayloadContainsTimeoutAndCsvHeader(): void
    {
        $service = new RouteNameEvalOsm('http://example.test/api', '', new Client());
        $route = $this->createRouteWithCoordinates(20);

        $payload = $this->invokeCreateOsmPayload($service, $route, 5.0);

        self::assertStringStartsWith('[out:csv(', $payload);
        self::assertStringContainsString('[timeout:180]', $payload);
    }

    public function testCreateOsmPayloadContainsWellFormedBoundingBox(): void
    {
        $service = new RouteNameEvalOsm('http://example.test/api', '', new Client());
        $route = $this->createRouteWithCoordinates(20);

        $payload = $this->invokeCreateOsmPayload($service, $route, 5.0);

        self::assertMatchesRegularExpression(
            '/\[bbox:-?\d+\.\d{6},-?\d+\.\d{6},-?\d+\.\d{6},-?\d+\.\d{6}\];/',
            $payload
        );
    }

    public function testCreateOsmPayloadUsesDefaultRadiusWhenWithinLimits(): void
    {
        $service = new RouteNameEvalOsm('http://example.test/api', '', new Client());
        $route = $this->createRouteWithCoordinates(20);

        $payload = $this->invokeCreateOsmPayload($service, $route, 5.0);

        self::assertStringContainsString('around:20.00,', $payload);
    }

    /**
     * A 15km route exceeds the limit (15000/20 = 750 > 600), so the radius
     * is recalculated as distanceInMeters/AROUND_POS_LIMIT = 15000/600 =
     * 25.0, which is still within RADIUS_LIMIT (40.0) and therefore not
     * clamped a second time.
     */
    public function testCreateOsmPayloadClampsRadiusButStaysWithinRadiusLimit(): void
    {
        $service = new RouteNameEvalOsm('http://example.test/api', '', new Client());
        $route = $this->createRouteWithCoordinates(20);

        $payload = $this->invokeCreateOsmPayload($service, $route, 15.0);

        self::assertStringContainsString('around:25.00,', $payload);
    }

    /**
     * A 30km route pushes the recalculated radius (30000/600 = 50.0) beyond
     * RADIUS_LIMIT (40.0), so it gets clamped a second time down to exactly
     * RADIUS_LIMIT.
     */
    public function testCreateOsmPayloadClampsRadiusToRadiusLimitForVeryLongRoutes(): void
    {
        $service = new RouteNameEvalOsm('http://example.test/api', '', new Client());
        $route = $this->createRouteWithCoordinates(20);

        $payload = $this->invokeCreateOsmPayload($service, $route, 30.0);

        self::assertStringContainsString('around:40.00,', $payload);
    }

    public function testCreateOsmPayloadVisitsEachCoordinateExactlyOnceForDenseRoutes(): void
    {
        $service = new RouteNameEvalOsm('http://example.test/api', '', new Client());
        $pointCount = 20;
        $route = $this->createRouteWithCoordinates($pointCount);

        $payload = $this->invokeCreateOsmPayload($service, $route, 5.0);

        self::assertMatchesRegularExpression('/around:20\.00,([\d.,-]+)\)\["name"\]->\.named_node;/', $payload, $payload);
        preg_match('/around:20\.00,([\d.,-]+)\)\["name"\]->\.named_node;/', $payload, $matches);
        $numbers = explode(',', $matches[1]);

        self::assertCount(($pointCount + 2) * 2, $numbers);
        self::assertCount($pointCount * 2, array_unique($numbers), 'no index other than the first/last should ever be duplicated');
    }
}
