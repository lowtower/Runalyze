<?php

namespace Runalyze\Service\WeatherForecast\Strategy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Mapping\OpenWeatherMapMapping;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Exception\TooOldForCurrentConditionsException;
use Runalyze\Service\WeatherForecast\Location;

/**
 * Forecast-strategy for using openweathermap.org
 *
 * This weather forecast strategy uses the api of openweathermap.org
 * To use this api, a location has to be set.
 *
 * @see http://openweathermap.org/weather-conditions
 */
class OpenWeatherMap implements StrategyInterface, LoggerAwareInterface, LastExceptionAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    public const URL = 'http://api.openweathermap.org/data/2.5/weather';

    /**
     * @var string
     */
    public const URL_HISTORICAL = 'http://api.openweathermap.org/data/2.5/history';

    /**
     * @var int [s]
     */
    public const DEFAULT_MAX_AGE_IN_SECONDS = 7200;

    protected ?\Throwable $LastException = null;

    protected int $MaxAgeInSeconds;

    public function __construct(
        protected string $ApiKey,
        protected ?string $Proxy,
        protected Client $HttpClient,
        ?LoggerInterface $logger = null,
        ?int $maxAgeInSeconds = null
    ) {
        $this->logger = $logger ?: new NullLogger();
        $this->MaxAgeInSeconds = $maxAgeInSeconds ?? self::DEFAULT_MAX_AGE_IN_SECONDS;
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

        return $this->getWeatherDataFromResult($result);
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function getWeatherDataFromResult(array $result): \Runalyze\Parser\Activity\Common\Data\WeatherData
    {
        $data = new WeatherData();
        $data->Source = WeatherSourceProfile::OPEN_WEATHER_MAP;

        if (isset($result['main'])) {
            $this->setMainConditions($data, $result['main']);
        }

        if (isset($result['wind'])) {
            $this->setWindConditions($data, $result['wind']);
        }

        $data->InternalConditionId = $this->getInternalConditionId($result);

        return $data;
    }

    /**
     * @param array<string, mixed> $main
     */
    protected function setMainConditions(WeatherData $data, array $main)
    {
        if (isset($main['temp'])) {
            $data->Temperature = (int) round($main['temp'] - 273.15);
        }

        if (isset($main['pressure'])) {
            $data->AirPressure = (int) round($main['pressure']);
        }

        if (isset($main['humidity'])) {
            $data->Humidity = (int) round($main['humidity']);
        }
    }

    /**
     * @param array<string, mixed> $wind
     */
    protected function setWindConditions(WeatherData $data, array $wind)
    {
        if (isset($wind['speed'])) {
            $data->WindSpeed = 3.6 * $wind['speed'];
        }

        if (isset($wind['deg'])) {
            $data->WindDirection = ((int) round($wind['deg'])) % 360;
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function getInternalConditionId(array $result): int
    {
        if (isset($result['weather']) && isset($result['weather'][0]) && isset($result['weather'][0]['id'])) {
            return (new OpenWeatherMapMapping())->toInternal($result['weather'][0]['id']);
        }

        return WeatherConditionProfile::UNKNOWN;
    }

    protected function tryToLoadForecast(Location $location): array
    {
        if (! $location->isOlderThan($this->MaxAgeInSeconds)) {
            try {
                $headers = [];
                if (! empty($this->Proxy)) {
                    $headers['proxy'] = $this->Proxy;
                }

                $response = $this->HttpClient->get($this->getUrlFor($location), $headers);
                $result = json_decode($response->getBody()->getContents(), true);

                if (is_array($result)) {
                    if (isset($result['list']) && ! empty($result['list'])) {
                        return $result['list'][0];
                    }

                    if (isset($result['cod']) && (int) $result['cod'] >= 400) {
                        $this->LastException = new \RuntimeException(
                            sprintf(
                                'OpenWeatherMap request failed (cod: %s): %s',
                                $result['cod'],
                                $result['message'] ?? 'unknown error'
                            )
                        );

                        return [];
                    }

                    return $result;
                }
            } catch (RequestException $e) {
                $this->logger->warning('OpenWeatherMap API request failed.', [
                    'exception' => $e,
                ]);

                $this->LastException = $e;
            }
        } else {
            $ageInSeconds = $location->hasDateTime() ? (time() - $location->getTimestamp()) : null;

            $this->LastException = new TooOldForCurrentConditionsException(sprintf(
                'OpenWeatherMap only provides current conditions; activity is %s old, exceeding the configured %ds threshold.',
                $ageInSeconds !== null ? round($ageInSeconds / 3600, 1) . 'h' : 'unknown age',
                $this->MaxAgeInSeconds
            ));
        }

        return [];
    }

    protected function getUrlFor(Location $location): string
    {
        $parameter = [];

        if ($location->hasPosition()) {
            $parameter[] = 'lat=' . $location->getLatitude();
            $parameter[] = 'lon=' . $location->getLongitude();
        } else {
            $parameter[] = 'q=' . $location->getLocationName();
        }

        return sprintf(
            '%s?%s&APPID=%s',
            self::URL,
            implode('&', $parameter),
            $this->ApiKey
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function updateLocationFromResult(Location $location, array $result)
    {
        if (isset($result['coord']) && isset($result['coord']['lat']) && isset($result['coord']['lon'])) {
            $location->setPosition($result['coord']['lat'], $result['coord']['lon']);
        }

        if (isset($result['dt']) && is_numeric($result['dt'])) {
            $location->setDateTime((new \DateTime())->setTimestamp($result['dt']));
        }
    }
}
