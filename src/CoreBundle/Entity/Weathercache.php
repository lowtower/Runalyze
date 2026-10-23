<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Bundle\CoreBundle\Repository\WeathercacheRepository;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\Location;

#[ORM\Table(name: 'weathercache')]
#[ORM\Entity(repositoryClass: WeathercacheRepository::class)]
class Weathercache
{
    public const int GEOHASH_PRECISION_DATABASE = 5;

    public const int GEOHASH_PRECISION_LOOKUP = 4;

    #[ORM\Column(name: 'geohash', type: \Doctrine\DBAL\Types\Types::STRING, length: 5, nullable: false, options: [
        'fixed' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $geohash = '';

    #[ORM\Column(name: 'time', type: \Doctrine\DBAL\Types\Types::INTEGER, precision: 11, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $time;

    /**
     * @var ?int [°C]
     */
    #[ORM\Column(name: 'temperature', type: 'tinyint', nullable: true)]
    private ?int $temperature = null;

    /**
     * @var ?int [km/h]
     */
    #[ORM\Column(name: 'wind_speed', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $windSpeed = null;

    /**
     * @var ?int [°]
     */
    #[ORM\Column(name: 'wind_deg', type: \Doctrine\DBAL\Types\Types::SMALLINT, precision: 3, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $windDeg = null;

    /**
     * @var ?int [%]
     */
    #[ORM\Column(name: 'humidity', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $humidity = null;

    /**
     * @var ?int [hPa]
     */
    #[ORM\Column(name: 'pressure', type: \Doctrine\DBAL\Types\Types::SMALLINT, precision: 4, nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $pressure = null;

    #[ORM\Column(name: 'weatherid', type: \Doctrine\DBAL\Types\Types::SMALLINT, nullable: false)]
    private int $weatherid = 1;

    #[ORM\Column(name: 'weather_source', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private ?int $weatherSource = null;

    public function setGeohash(string $geohash): self
    {
        $this->geohash = substr($geohash, 0, self::GEOHASH_PRECISION_DATABASE);

        return $this;
    }

    public function getGeohash(): string
    {
        return $this->geohash;
    }

    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param ?int $temperature [°C]
     */
    public function setTemperature(?int $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @return ?int [°C]
     */
    public function getTemperature(): ?int
    {
        return $this->temperature;
    }

    /**
     * @param ?int $windSpeed [km/h]
     */
    public function setWindSpeed(?int $windSpeed): self
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    /**
     * @return ?int [km/h]
     */
    public function getWindSpeed(): ?int
    {
        return $this->windSpeed;
    }

    /**
     * @param ?int $windDeg [°]
     */
    public function setWindDeg(?int $windDeg): self
    {
        $this->windDeg = $windDeg;

        return $this;
    }

    /**
     * @return ?int [°]
     */
    public function getWindDeg(): ?int
    {
        return $this->windDeg;
    }

    /**
     * @param ?int $humidity [%]
     */
    public function setHumidity(?int $humidity): self
    {
        $this->humidity = $humidity;

        return $this;
    }

    /**
     * @return ?int [%]
     */
    public function getHumidity(): ?int
    {
        return $this->humidity;
    }

    /**
     * @param ?int $pressure [hPa]
     */
    public function setPressure(?int $pressure): self
    {
        $this->pressure = $pressure;

        return $this;
    }

    /**
     * @return ?int [hPa]
     */
    public function getPressure(): ?int
    {
        return $this->pressure;
    }

    public function setWeatherid(int $weatherid): self
    {
        $this->weatherid = $weatherid;

        return $this;
    }

    public function getWeatherid(): int
    {
        return $this->weatherid;
    }

    public function setWeatherSource(?int $weatherSource): self
    {
        $this->weatherSource = $weatherSource;

        return $this;
    }

    public function getWeatherSource(): ?int
    {
        return $this->weatherSource;
    }

    public function getAsWeatherData(): \Runalyze\Parser\Activity\Common\Data\WeatherData
    {
        $data = new WeatherData();
        $data->InternalConditionId = $this->weatherid;
        $data->Temperature = $this->temperature;
        $data->AirPressure = $this->pressure;
        $data->Humidity = $this->humidity;
        $data->WindSpeed = $this->windSpeed;
        $data->WindDirection = $this->windDeg;
        $data->Source = $this->weatherSource;

        return $data;
    }

    public function setWeatherData(WeatherData $data): void
    {
        $this->weatherid = $data->InternalConditionId;
        $this->temperature = $data->Temperature;
        $this->pressure = $data->AirPressure;
        $this->humidity = $data->Humidity;
        $this->windSpeed = $data->WindSpeed;
        $this->windDeg = $data->WindDirection;
        $this->weatherSource = $data->Source;
    }

    /**
     * @throws \InvalidArgumentException if the location has no position or no date/time
     */
    public function setLocation(Location $location): void
    {
        $geohash = $location->getGeohash();
        $timestamp = $location->getTimestamp();

        if ($geohash === null || $timestamp === null) {
            throw new \InvalidArgumentException('Location needs both a position and a date/time to be cached.');
        }

        $this->setGeohash($geohash);
        $this->setTime($timestamp);
    }
}
