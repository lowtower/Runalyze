<?php

namespace Runalyze\Service\WeatherForecast\Strategy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Mapping\MeteostatNetMapping;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;

/**
 * Weather-strategy for using meteostat.net.
 * meteostat.net is a free cost service for get historical weather data and is now hosted via rapidapi.com.
 * It loads for the GPS lat/lon the hourly (historical) weather-data and fetch the timed data in the mid of the activity.
 * #TSC
 *
 * @see https://meteostat.net / https://dev.meteostat.net/api/point/hourly.html#endpoint
 */
class MeteostatNet implements StrategyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    public const URL = 'https://meteostat.p.rapidapi.com/point/hourly';

    /**
     * @var Client
     */
    protected $HttpClient;

    /**
     * @param string $ApiKey
     * @param string $Proxy
     */
    public function __construct(
        protected $ApiKey,
        protected $Proxy,
        Client $client,
        ?LoggerInterface $logger = null
    ) {
        $this->HttpClient = $client;
        $this->logger = $logger;
    }

    public function isPossible(): bool
    {
        return (string) $this->ApiKey !== '';
    }

    public function isCachable(): bool
    {
        return true;
    }

    public function loadForecast(Location $location)
    {
        $result = $this->tryToLoadForecast($location);

        if (! is_array($result) || $result === []) {
            return null;
        }

        $this->updateLocationFromResult($location, $result);

        return $this->getWeatherDataFromResult($result);
    }

    /**
     * @param array<string, mixed> $currently
     */
    protected function getWeatherDataFromResult(array $currently): \Runalyze\Parser\Activity\Common\Data\WeatherData
    {
        $data = new WeatherData();
        $data->Source = WeatherSourceProfile::METEOSTAT_NET;

        if (isset($currently['temp'])) {
            $data->Temperature = (int) round($currently['temp']);
        }

        if (isset($currently['pres'])) {
            $data->AirPressure = (int) round($currently['pres']);
        }

        if (isset($currently['rhum'])) {
            $data->Humidity = (int) round($currently['rhum']);
        }

        if (isset($currently['wspd'])) {
            $data->WindSpeed = (int) round($currently['wspd']);
        }

        if (isset($currently['wdir'])) {
            $data->WindDirection = (int) round($currently['wdir']);
        }

        $data->InternalConditionId = $this->getInternalConditionId($currently);

        return $data;
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function getInternalConditionId(array $result): int
    {
        if (isset($result['coco'])) {
            $this->logger->debug(sprintf('meteostat.net weather condition %s.', $result['coco']));

            return (new MeteostatNetMapping())->toInternal($result['coco']);
        }

        return WeatherConditionProfile::UNKNOWN;
    }

    /**
     * @return array
     */
    protected function tryToLoadForecast(Location $location)
    {
        $url = $this->getUrlFor($location);
        $this->logger->debug(sprintf('url >> %s.', $url));

        try {
            $headers = [
                'headers' => [
                    'x-rapidapi-host' => 'meteostat.p.rapidapi.com',
                    'x-rapidapi-key' => $this->ApiKey,
                ],
            ];
            if (! empty($this->Proxy)) {
                $headers['proxy'] = $this->Proxy;
            }

            $response = $this->HttpClient->request('GET', $url, $headers);
            $statusCode = $response->getStatusCode();

            $repCont = $response->getBody()
                ->getContents();
            $this->logger->info(sprintf('status-code >> %d result-raw >> %s', $statusCode, $repCont));

            $result = json_decode($repCont, true);

            // we've get a json with 'meta' and 'data'; 'data' contains one object for every hour
            if (is_array($result)) {
                if (isset($result['data']) && ! empty($result['data'])) {
                    // retrieve the data-element with our trainings-time
                    return $this->getDataElementOfTime($location->hasDateTime() ? $location->getDateTime() : time(), $result['data']);
                }
            }
        } catch (RequestException $requestException) {
            $this->logger->error('MeteostatNet API request failed.', [
                'exception' => $requestException,
            ]);
            throw new \RuntimeException('MeteostatNet request resulted in an error: ' . $requestException->getMessage(), 0, $requestException);
        }

        return [];
    }

    protected function getDataElementOfTime($time, $data)
    {
        // get activity-time in string-format
        $ymdhms = $time->format('Y-m-d H:i:s');
        $this->logger->debug(sprintf('searching data elements with date %s', $ymdhms));

        // the array is sorted from 00:00 to 23:00; do the reverse and select the right
        foreach (array_reverse($data) as &$value) {
            if (isset($value['time']) && $ymdhms >= $value['time']) {
                $this->logger->debug(sprintf('found data elements for time %s >%s<', $ymdhms, print_r($value, true)));
                return $value;
            }
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
            $this->logger->error('MeteostatNet cant build URL because we have no location.');
            throw new \Exception('MeteostatNet cant build URL because we have no location.');
        }

        $dateTime = $location->getDateTime();
        if (! $dateTime instanceof \DateTime) {
            try {
                $timezone = new \DateTimeZone($location->getTimezone());
            } catch (\Exception) {
                $timezone = new \DateTimeZone(date_default_timezone_get());
            }

            $dateTime = new \DateTime('now', $timezone);
        }

        $ymd = $dateTime->format('Y-m-d');
        $parameter[] = 'start=' . $ymd;
        $parameter[] = 'end=' . $ymd;
        $parameter[] = 'tz=' . $location->getTimezone();

        return sprintf(
            '%s?%s',
            self::URL,
            implode('&', $parameter)
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function updateLocationFromResult(Location $location, array $result)
    {
        if (isset($result['time'])) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $result['time']);

            $location->setDateTime($date);
        }
    }
}
