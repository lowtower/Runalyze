<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal\Service;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Weathercache;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class WeatherControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();

        $this->getEntityManager()
            ->createQuery('DELETE FROM ' . Weathercache::class)->execute();
    }

    /**
     * @return array<string, mixed>
     * @param array<string, string> $query
     */
    private function requestWeatherData(Account $account, array $query = []): array
    {
        $this->loginClient($this->Client, $account, 'default');
        $this->Client->request('GET', '/_internal/service/weather?' . http_build_query($query));

        $this->isSuccessful($this->Client->getResponse());

        return json_decode($this->Client->getResponse()->getContent(), true);
    }

    public function testFetchWeatherDataActionRequiresAuthentication(): void
    {
        $this->Client->request('GET', '/_internal/service/weather?city=Berlin');

        $this->isSuccessful($this->Client->getResponse(), false);
    }

    public function testFetchWeatherDataActionReturnsEmptyPositionResponseWhenNoPositionCanBeDetermined(): void
    {
        $data = $this->requestWeatherData($this->Account, [
            'city' => 'Berlin',
        ]);

        $this->assertSame(
            [
                'empty' => true,
                'location' => [
                    'name' => 'Berlin',
                    'lat' => null,
                    'lng' => null,
                    'date' => '',
                ],
                'source' => [
                    'id' => null,
                    'name' => '',
                ],
                'weatherid' => null,
                'temperature' => null,
                'wind_speed' => null,
                'wind_deg' => null,
                'humidity' => null,
                'pressure' => null,
            ],
            $data,
            'When no position is available, the response must not contain a "sources" key - that is only added once a position exists but no weather data could be found for it.'
        );
    }

    public function testFetchWeatherDataActionIgnoresMalformedLatLngAndFallsBackToNoPositionResponse(): void
    {
        $data = $this->requestWeatherData($this->Account, [
            'city' => 'Berlin',
            'latlng' => '52.5',
        ]);

        $this->assertTrue($data['empty']);
        $this->assertNull($data['location']['lat']);
        $this->assertNull($data['location']['lng']);
        $this->assertArrayNotHasKey('sources', $data);
    }

    /**
     * @throws \Exception
     */
    public function testFetchWeatherDataActionReturnsSourceDiagnosticsWhenPositionIsKnownButNoForecastIsAvailable(): void
    {
        $data = $this->requestWeatherData($this->Account, [
            'city' => 'Berlin',
            'latlng' => '52.5,13.4',
        ]);

        $this->assertTrue($data['empty']);
        $this->assertSame('Berlin', $data['location']['name']);
        $this->assertSame(52.5, $data['location']['lat']);
        $this->assertSame(13.4, $data['location']['lng']);

        $this->assertArrayHasKey('sources', $data);
        $this->assertSame(
            [
                [
                    'source' => 'openweathermap',
                    'reason' => 'not_possible',
                    'detail' => null,
                ],
                [
                    'source' => 'meteostatnet',
                    'reason' => 'not_possible',
                    'detail' => null,
                ],
            ],
            $data['sources'],
            'With empty API keys in the test environment, both external strategies must report ' .
            '"not_possible" and the cache strategy must be filtered out entirely.'
        );
    }

    public function testFetchWeatherDataActionIgnoresInvalidDateQueryParameterInsteadOfFailing(): void
    {
        $data = $this->requestWeatherData($this->Account, [
            'city' => 'Berlin',
            'latlng' => '52.5,13.4',
            'date' => 'this-is-not-a-valid-date',
        ]);

        $this->assertSame('', $data['location']['date']);
    }

    public function testFetchWeatherDataActionIgnoresNonNumericTimeQueryParameterInsteadOfFailing(): void
    {
        $data = $this->requestWeatherData($this->Account, [
            'city' => 'Berlin',
            'latlng' => '52.5,13.4',
            'time' => 'not-numeric',
        ]);

        $this->assertSame('', $data['location']['date']);
    }

    public function testFetchWeatherDataActionUsesValidNumericTimeQueryParameter(): void
    {
        $timestamp = 1700000000;

        $data = $this->requestWeatherData($this->Account, [
            'city' => 'Berlin',
            'latlng' => '52.5,13.4',
            'time' => (string) $timestamp,
        ]);

        $this->assertSame(
            new \DateTime('@' . $timestamp)->format('c'),
            $data['location']['date']
        );
    }
}
