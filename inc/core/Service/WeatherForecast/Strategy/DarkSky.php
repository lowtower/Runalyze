<?php

namespace Runalyze\Service\WeatherForecast\Strategy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Mapping\DarkskyMapping;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;

/**
 * Forecast-strategy for using darksky
 *
 * This weather forecast strategy uses the api of forecast.io
 * API documentation: https://darksky.net/dev/
 * To use this api, a location has to be set.
 *
 * @see https://developer.forecast.io/docs/v2
 */
class DarkSky implements StrategyInterface, LoggerAwareInterface, LastExceptionAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    public const URL = 'https://api.darksky.net/forecast/';

    /**
     * NOTE: newly added - see MeteostatNet.php / OpenWeatherMap.php for the
     * shared rationale.
     */
    protected ?\Throwable $LastException = null;

    public function __construct(
        protected string $ApiKey,
        protected Client $HttpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?: new NullLogger();
    }

    public function isPossible(): bool
    {
        return $this->ApiKey !== '';
    }

    public function isCachable(): bool
    {
        return true;
    }

    public function getLastException(): ?\Throwable
    {
        return $this->LastException;
    }

    public function loadForecast(Location $location): ?WeatherData
    {
        $this->LastException = null;

        $result = $this->tryToLoadForecast($location);

        if ($result === []) {
            return null;
        }

        $this->updateLocationFromResult($location, $result);

        return $this->getWeatherDataFromResult($result['currently']);
    }

    /**
     * @param array<string, mixed> $currently
     */
    protected function getWeatherDataFromResult(array $currently): WeatherData
    {
        $data = new WeatherData();
        $data->Source = WeatherSourceProfile::DARK_SKY;

        if (isset($currently['temperature'])) {
            $data->Temperature = ($currently['temperature'] - 32.0) / 1.8;
        }

        if (isset($currently['pressure'])) {
            $data->AirPressure = (int) round($currently['pressure']);
        }

        if (isset($currently['humidity'])) {
            $data->Humidity = (int) round($currently['humidity'] * 100);
        }

        if (isset($currently['windSpeed'])) {
            $data->WindSpeed = $currently['windSpeed'] / 0.621371192;
        }

        if (isset($currently['windBearing'])) {
            $data->WindDirection = (int) round($currently['windBearing']);
        }

        $data->InternalConditionId = $this->getInternalConditionId($currently);

        return $data;
    }

    /**
     * @param array<string, mixed> $currently
     */
    protected function getInternalConditionId(array $currently): int
    {
        if (isset($currently['icon'])) {
            return new DarkskyMapping()->toInternal($currently['icon']);
        }

        return WeatherConditionProfile::UNKNOWN;
    }

    protected function tryToLoadForecast(Location $location): array
    {
        if ($location->hasPosition()) {
            $url = sprintf(
                '%s/%s,%s,%s',
                self::URL . $this->ApiKey,
                (string) $location->getLatitude(),
                (string) $location->getLongitude(),
                $location->hasDateTime() ? (string) $location->getTimestamp() : time()
            );

            try {
                $response = $this->HttpClient->get($url);
                $result = json_decode($response->getBody()->getContents(), true);

                if (is_array($result) && isset($result['currently'])) {
                    return $result;
                }

                $this->LastException = new \RuntimeException(
                    'DarkSky returned a response without forecast data.'
                );
            } catch (RequestException $e) {
                $this->logger->warning('DarkSky API request failed.', [
                    'exception' => $e,
                ]);
                $this->LastException = $e;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function updateLocationFromResult(Location $location, array $result)
    {
        if (isset($result['latitude']) && isset($result['longitude'])) {
            $location->setPosition($result['latitude'], $result['longitude']);
        }

        if (isset($result['dt']) && is_numeric($result['dt'])) {
            $location->setDateTime(new \DateTime()->setTimestamp($result['dt']));
        }
    }
}
