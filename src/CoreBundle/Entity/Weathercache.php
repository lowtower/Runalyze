<?php

namespace Runalyze\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Service\WeatherForecast\Location;

#[ORM\Table(name: 'weathercache')]
#[ORM\Entity(repositoryClass: WeathercacheRepository::class)]
class Weathercache
{
    /**
     * @var int
     */
    public const GEOHASH_PRECISION_DATABASE = 5;

    /**
     * @var int
     */
    public const GEOHASH_PRECISION_LOOKUP = 4;

    #[ORM\Column(name: 'geohash', type: 'string', length: 5, nullable: false, options: [
        'fixed' => true,
    ])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $geohash = '';

    #[ORM\Column(name: 'time', type: 'integer', precision: 11, nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private ?int $time = null;

    /**
     * @var int|null [°C]
     */
    #[ORM\Column(name: 'temperature', type: 'tinyint', nullable: true)]
    private $temperature;

    /**
     * @var int|null [km/h]
     */
    #[ORM\Column(name: 'wind_speed', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $windSpeed;

    /**
     * @var int|null [°]
     */
    #[ORM\Column(name: 'wind_deg', type: 'smallint', precision: 3, nullable: true, options: [
        'unsigned' => true,
    ])]
    private $windDeg;

    /**
     * @var int|null [%]
     */
    #[ORM\Column(name: 'humidity', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $humidity;

    /**
     * @var int [hPa]
     */
    #[ORM\Column(name: 'pressure', type: 'smallint', precision: 4, nullable: true, options: [
        'unsigned' => true,
    ])]
    private $pressure;

    /**
     * @var int
     */
    #[ORM\Column(name: 'weatherid', type: 'smallint', nullable: false)]
    private $weatherid = 1;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'weather_source', type: 'tinyint', nullable: true, options: [
        'unsigned' => true,
    ])]
    private $weatherSource;

    /**
     * @param string $geohash
     */
    public function setGeohash($geohash): self
    {
        $this->geohash = substr($geohash, 0, self::GEOHASH_PRECISION_DATABASE);

        return $this;
    }

    /**
     * @return string
     */
    public function getGeohash()
    {
        return $this->geohash;
    }

    /**
     * @param int $time
     */
    public function setTime($time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int|null $temperature [°C]
     */
    public function setTemperature($temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @return int|null [°C]
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * @param int|null $windSpeed [km/h]
     */
    public function setWindSpeed($windSpeed): self
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    /**
     * @return int|null [km/h]
     */
    public function getWindSpeed()
    {
        return $this->windSpeed;
    }

    /**
     * @param int|null $windDeg [°]
     */
    public function setWindDeg($windDeg): self
    {
        $this->windDeg = $windDeg;

        return $this;
    }

    /**
     * @return int|null [°]
     */
    public function getWindDeg()
    {
        return $this->windDeg;
    }

    /**
     * @param int|null $humidity [%]
     */
    public function setHumidity($humidity): self
    {
        $this->humidity = $humidity;

        return $this;
    }

    /**
     * @return int|null [%]
     */
    public function getHumidity()
    {
        return $this->humidity;
    }

    /**
     * @param int|null $pressure [hPa]
     */
    public function setPressure($pressure): self
    {
        $this->pressure = $pressure;

        return $this;
    }

    /**
     * @return int|null [hPa]
     */
    public function getPressure()
    {
        return $this->pressure;
    }

    /**
     * @param int $weatherid
     */
    public function setWeatherid($weatherid): self
    {
        $this->weatherid = $weatherid;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeatherid()
    {
        return $this->weatherid;
    }

    /**
     * @param int|null $weatherSource
     */
    public function setWeatherSource($weatherSource): self
    {
        $this->weatherSource = $weatherSource;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWeatherSource()
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

    public function setLocation(Location $location): void
    {
        $this->setGeohash($location->getGeohash());
        $this->setTime($location->getTimestamp());
    }
}
