<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\Service\RouteNameEvaluation\OsmCsvData;
use Runalyze\Service\RouteNameEvaluation\OsmCsvDataCollection;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalOsm;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult;
use Stringable;
use Throwable;

class RouteNameEvalOsmRecordingLogger extends AbstractLogger
{
    /**
     * @var array<int, array{level: mixed, message: string, context: array<mixed>}>
     */
    public array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}

#[\PHPUnit\Framework\Attributes\Group('default')]
class RouteNameEvalOsmTest extends TestCase
{
    private const string API_URL = 'http://example.test/api';

    private RouteNameEvalOsm $underTest;

    private OsmCsvDataCollection&Stub $coll;

    protected function setUp(): void
    {
        $this->underTest = new RouteNameEvalOsm('', '', new Client());
        $this->coll = $this->createStub(OsmCsvDataCollection::class);
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
     * @param array<int, ResponseInterface|Throwable> $responses
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

    /**
     * @param \GuzzleHttp\Psr7\Response[]|\GuzzleHttp\Exception\RequestException[] $responses
     */
    private function createServiceWithResponses(array $responses, array &$historyContainer = [], string $proxy = '', ?RouteNameEvalOsmRecordingLogger $logger = null): RouteNameEvalOsm
    {
        $client = $this->createClientWithResponses($responses, $historyContainer);

        if ($logger instanceof RouteNameEvalOsmRecordingLogger) {
            return new RouteNameEvalOsm(self::API_URL, $proxy, $client, $logger);
        }

        return new RouteNameEvalOsm(self::API_URL, $proxy, $client);
    }

    private function invokeRequest(RouteNameEvalOsm $service, ?int $routeId, string $payload): string
    {
        return new ReflectionMethod($service, 'request')->invoke($service, $routeId, $payload);
    }

    private function invokeCreateOsmPayload(RouteNameEvalOsm $service, Route $route, float $distance): string
    {
        return new ReflectionMethod($service, 'createOsmPayload')->invoke($service, $route, $distance);
    }

    /**
     * @param string[] $peaks
     * @param string[] $places
     * @param string[] $huts
     * @param string[] $ways
     * @param string[] $infos
     */
    private function arrangeCategoryNames(array $peaks, array $places, array $huts, array $ways, array $infos): void
    {
        $this->coll->method('getNames')
            ->willReturnMap([
                [OsmCsvData::$CAT_PEAK, $peaks],
                [OsmCsvData::$CAT_PLACE, $places],
                [OsmCsvData::$CAT_HUT, $huts],
                [OsmCsvData::$CAT_WAY, $ways],
                [OsmCsvData::$CAT_INFO, $infos],
            ]);
    }

    /**
     * @param string[] $peaks
     * @param string[] $places
     * @param string[] $huts
     * @param string[] $ways
     * @param string[] $infos
     */
    #[DataProvider('categoryCombinations')]
    public function testCreateResultCombinesCategoriesIntoNamesAndNotes(
        array $peaks,
        array $places,
        array $huts,
        array $ways,
        array $infos,
        string $expectedNames,
        string $expectedNotes
    ): void {
        $this->arrangeCategoryNames($peaks, $places, $huts, $ways, $infos);

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertSame($expectedNames, $result->getNames());
        $this->assertSame($expectedNotes, $result->getNotes());
    }

    public static function categoryCombinations(): iterable
    {
        yield 'all categories filled' => [
            ['apeak1', 'apeak2'], ['aplace'], ['ahut'], ['away'], ['ainfo'],
            'apeak1 - apeak2 - aplace',
            "Verpflegung: ahut\nWege: away\nInfo: ainfo\n",
        ];
        yield 'only peak' => [
            ['apeak1'], [], [], [], [],
            'apeak1',
            '',
        ];
        yield 'peak and hut missing' => [
            [], ['aplace1', 'aplace2'], [], ['away'], ['ainfo'],
            'aplace1 - aplace2',
            "Wege: away\nInfo: ainfo\n",
        ];
        yield 'only places' => [
            [], ['aplace1', 'aplace2'], [], [], [],
            'aplace1 - aplace2',
            '',
        ];
        yield 'peak and place missing' => [
            [], [], ['ahut1', 'ahut2'], ['away1', 'away2'], ['ainfo'],
            'ahut1 - ahut2 - away1 - away2',
            "Info: ainfo\n",
        ];
        yield 'peak, place and hut missing' => [
            [], [], [], ['away1', 'away2'], ['ainfo'],
            'away1 - away2',
            "Info: ainfo\n",
        ];
        yield 'only infos' => [
            [], [], [], [], ['ainfo1', 'ainfo2'],
            'ainfo1 - ainfo2',
            '',
        ];
    }

    public function testCreateResultReturnsNullWhenAllCategoriesAreEmpty(): void
    {
        $this->arrangeCategoryNames([], [], [], [], []);

        $this->assertNull($this->underTest->createResult($this->getSportRunning(), $this->coll));
    }

    public function testCreateResultListsHutAsNoteForRunningButAsNameForMountaineering(): void
    {
        $this->arrangeCategoryNames([], ['aplace'], ['ahut'], [], []);

        $runningResult = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertSame('aplace', $runningResult->getNames());
        $this->assertSame("Verpflegung: ahut\n", $runningResult->getNotes());

        $mountainResult = $this->underTest->createResult($this->getSportMountain(), $this->coll);

        $this->assertSame('aplace - ahut', $mountainResult->getNames());
        $this->assertSame('', $mountainResult->getNotes());
    }

    public function testCreateResultUsesAtMostSevenNames(): void
    {
        $places = [];
        for ($i = 0; $i < 8; $i++) {
            $places[] = str_repeat((string) $i, 32);
        }

        $this->arrangeCategoryNames([], $places, [], [], []);

        $result = $this->underTest->createResult($this->getSportRunning(), $this->coll);

        $this->assertSame(implode(' - ', array_slice($places, 0, 7)), $result->getNames());
    }

    public function testEvaluateReturnsNullImmediatelyWhenUrlIsEmpty(): void
    {
        $service = new RouteNameEvalOsm('', '', $this->createClientWithResponses([]));

        $result = $service->evaluate($this->getSportRunning(), $this->createRouteWithCoordinates(5), 5.0);

        self::assertNull($result);
    }

    public function testEvaluateReturnsNullWhenHttpResponseBodyIsEmpty(): void
    {
        $history = [];
        $service = $this->createServiceWithResponses([new Response(200, [], '')], $history);

        $result = $service->evaluate($this->getSportRunning(), $this->createRouteWithCoordinates(20), 5.0);

        self::assertNull($result);
        self::assertCount(1, $history, 'exactly one HTTP request should have been made');
    }

    public function testEvaluateThrowsAndLogsErrorForNonSuccessStatusCode(): void
    {
        $logger = new RouteNameEvalOsmRecordingLogger();
        $service = $this->createServiceWithResponses([new Response(500, [], 'Internal Server Error')], logger: $logger);

        try {
            $service->evaluate($this->getSportRunning(), $this->createRouteWithCoordinates(20), 5.0);
            self::fail('Expected exception was not thrown.');
        } catch (Exception $exception) {
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
            new GuzzlePsr7Request('POST', self::API_URL)
        );
        $logger = new RouteNameEvalOsmRecordingLogger();
        $service = $this->createServiceWithResponses([$requestException], logger: $logger);

        try {
            $service->evaluate($this->getSportRunning(), $this->createRouteWithCoordinates(20), 5.0);
            self::fail('Expected exception was not thrown.');
        } catch (Exception $exception) {
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
        $service = $this->createServiceWithResponses([new Response(200, [], $rawTabSeparatedCsv)]);

        $result = $service->evaluate($this->getSportRunning(), $this->createRouteWithCoordinates(20), 5.0);

        self::assertInstanceOf(RouteNameEvalResult::class, $result);
        self::assertSame('Kramerspitz - Königsstand - Garmisch - See', $result->getNames());
        self::assertSame(
            "Wege: Kellerleitensteig, Kramerplateauweg, Kramersteig\nInfo: Wandergebiet Kramer\n",
            $result->getNotes()
        );
    }

    public function testRequestReplacesExistingPipesThenConvertsTabsToPipes(): void
    {
        $service = $this->createServiceWithResponses([new Response(200, [], "a|b\tc\td")]);

        self::assertSame('a/b|c|d', $this->invokeRequest($service, 1, 'payload'));
    }

    public function testRequestSendsPayloadAsRawBodyWithDataPrefix(): void
    {
        $history = [];
        $service = $this->createServiceWithResponses([new Response(200, [], 'ok')], $history);

        $this->invokeRequest($service, 1, 'my-payload-content');

        self::assertSame('data=my-payload-content', (string) $history[0]['request']->getBody());
    }

    public function testRequestSendsUserAgentHeader(): void
    {
        $history = [];
        $service = $this->createServiceWithResponses([new Response(200, [], 'ok')], $history);

        $this->invokeRequest($service, 1, 'payload');

        self::assertStringStartsWith('Runalyze/4.3.0 host-', $history[0]['request']->getHeaderLine('User-Agent'));
    }

    public function testRequestIncludesProxyOptionWhenProxyIsConfigured(): void
    {
        $history = [];
        $service = $this->createServiceWithResponses([new Response(200, [], 'ok')], $history, '127.0.0.1:8080');

        $this->invokeRequest($service, 1, 'payload');

        self::assertSame('127.0.0.1:8080', $history[0]['options']['proxy']);
    }

    public function testRequestOmitsProxyOptionWhenProxyIsEmpty(): void
    {
        $history = [];
        $service = $this->createServiceWithResponses([new Response(200, [], 'ok')], $history);

        $this->invokeRequest($service, 1, 'payload');

        self::assertSame('', $history[0]['options']['proxy']);
    }

    public function testCreateOsmPayloadContainsTimeoutAndCsvHeader(): void
    {
        $service = new RouteNameEvalOsm(self::API_URL, '', new Client());

        $payload = $this->invokeCreateOsmPayload($service, $this->createRouteWithCoordinates(20), 5.0);

        self::assertStringStartsWith('[out:csv(', $payload);
        self::assertStringContainsString('[timeout:180]', $payload);
    }

    public function testCreateOsmPayloadContainsWellFormedBoundingBox(): void
    {
        $service = new RouteNameEvalOsm(self::API_URL, '', new Client());

        $payload = $this->invokeCreateOsmPayload($service, $this->createRouteWithCoordinates(20), 5.0);

        self::assertMatchesRegularExpression(
            '/\[bbox:-?\d+\.\d{6},-?\d+\.\d{6},-?\d+\.\d{6},-?\d+\.\d{6}\];/',
            $payload
        );
    }

    public function testCreateOsmPayloadUsesDefaultRadiusWhenWithinLimits(): void
    {
        $service = new RouteNameEvalOsm(self::API_URL, '', new Client());

        $payload = $this->invokeCreateOsmPayload($service, $this->createRouteWithCoordinates(20), 5.0);

        self::assertStringContainsString('around:20.00,', $payload);
    }

    public function testCreateOsmPayloadRecalculatesRadiusFromDistanceForLongRoutes(): void
    {
        $service = new RouteNameEvalOsm(self::API_URL, '', new Client());

        $payload = $this->invokeCreateOsmPayload($service, $this->createRouteWithCoordinates(20), 15.0);

        self::assertStringContainsString('around:25.00,', $payload);
    }

    public function testCreateOsmPayloadClampsRadiusToRadiusLimitForVeryLongRoutes(): void
    {
        $service = new RouteNameEvalOsm(self::API_URL, '', new Client());

        $payload = $this->invokeCreateOsmPayload($service, $this->createRouteWithCoordinates(20), 30.0);

        self::assertStringContainsString('around:40.00,', $payload);
    }

    public function testCreateOsmPayloadVisitsEachCoordinateExactlyOnceForDenseRoutes(): void
    {
        $service = new RouteNameEvalOsm(self::API_URL, '', new Client());
        $pointCount = 20;

        $payload = $this->invokeCreateOsmPayload($service, $this->createRouteWithCoordinates($pointCount), 5.0);

        self::assertMatchesRegularExpression('/around:20\.00,([\d.,-]+)\)\["name"\]->\.named_node;/', $payload, $payload);
        preg_match('/around:20\.00,([\d.,-]+)\)\["name"\]->\.named_node;/', $payload, $matches);
        $numbers = explode(',', $matches[1]);

        self::assertCount(($pointCount + 2) * 2, $numbers);
        self::assertCount($pointCount * 2, array_unique($numbers), 'no index other than the first/last should ever be duplicated');
    }
}
