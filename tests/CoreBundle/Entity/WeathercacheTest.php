<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Weathercache;
use Runalyze\Parser\Activity\Common\Data\WeatherData;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;

#[\PHPUnit\Framework\Attributes\Group('default')]
class WeathercacheTest extends TestCase
{
    private Weathercache $weathercache;

    protected function setUp(): void
    {
        $this->weathercache = new Weathercache();
    }

    public function testGeohashDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->weathercache->getGeohash());
    }

    public function testWeatheridDefaultsToOne(): void
    {
        $this->assertSame(1, $this->weathercache->getWeatherid());
    }

    public function testSetGeohashTruncatesToDatabasePrecision(): void
    {
        $this->weathercache->setGeohash('u33dc1v0xv1n');

        $this->assertSame('u33dc', $this->weathercache->getGeohash());
    }

    public function testSetTimeStoresValue(): void
    {
        $this->weathercache->setTime(1234567890);

        $this->assertSame(1234567890, $this->weathercache->getTime());
    }

    public function testScalarWeatherValuesAreStoredAndSettersAreFluent(): void
    {
        $this->assertSame($this->weathercache, $this->weathercache->setTemperature(10));
        $this->assertSame($this->weathercache, $this->weathercache->setWindSpeed(25));
        $this->assertSame($this->weathercache, $this->weathercache->setWindDeg(302));
        $this->assertSame($this->weathercache, $this->weathercache->setHumidity(86));
        $this->assertSame($this->weathercache, $this->weathercache->setPressure(1033));
        $this->assertSame($this->weathercache, $this->weathercache->setWeatherid(WeatherConditionProfile::CLOUDY));
        $this->assertSame($this->weathercache, $this->weathercache->setWeatherSource(WeatherSourceProfile::OPEN_WEATHER_MAP));

        $this->assertSame(10, $this->weathercache->getTemperature());
        $this->assertSame(25, $this->weathercache->getWindSpeed());
        $this->assertSame(302, $this->weathercache->getWindDeg());
        $this->assertSame(86, $this->weathercache->getHumidity());
        $this->assertSame(1033, $this->weathercache->getPressure());
        $this->assertSame(WeatherConditionProfile::CLOUDY, $this->weathercache->getWeatherid());
        $this->assertSame(WeatherSourceProfile::OPEN_WEATHER_MAP, $this->weathercache->getWeatherSource());
    }

    public function testNullableScalarWeatherValuesCanBeCleared(): void
    {
        $this->weathercache->setTemperature(10);
        $this->weathercache->setWindSpeed(25);
        $this->weathercache->setWindDeg(302);
        $this->weathercache->setHumidity(86);
        $this->weathercache->setPressure(1033);
        $this->weathercache->setWeatherSource(WeatherSourceProfile::OPEN_WEATHER_MAP);

        $this->weathercache->setTemperature(null);
        $this->weathercache->setWindSpeed(null);
        $this->weathercache->setWindDeg(null);
        $this->weathercache->setHumidity(null);
        $this->weathercache->setPressure(null);
        $this->weathercache->setWeatherSource(null);

        $this->assertNull($this->weathercache->getTemperature());
        $this->assertNull($this->weathercache->getWindSpeed());
        $this->assertNull($this->weathercache->getWindDeg());
        $this->assertNull($this->weathercache->getHumidity());
        $this->assertNull($this->weathercache->getPressure());
        $this->assertNull($this->weathercache->getWeatherSource());
    }

    public function testGetAsWeatherDataMapsAllFields(): void
    {
        $this->weathercache->setWeatherid(WeatherConditionProfile::CLOUDY);
        $this->weathercache->setTemperature(10);
        $this->weathercache->setPressure(1033);
        $this->weathercache->setHumidity(86);
        $this->weathercache->setWindSpeed(25);
        $this->weathercache->setWindDeg(302);
        $this->weathercache->setWeatherSource(WeatherSourceProfile::OPEN_WEATHER_MAP);

        $data = $this->weathercache->getAsWeatherData();

        $this->assertSame(WeatherConditionProfile::CLOUDY, $data->InternalConditionId);
        $this->assertSame(10, $data->Temperature);
        $this->assertSame(1033, $data->AirPressure);
        $this->assertSame(86, $data->Humidity);
        $this->assertSame(25, $data->WindSpeed);
        $this->assertSame(302, $data->WindDirection);
        $this->assertSame(WeatherSourceProfile::OPEN_WEATHER_MAP, $data->Source);
    }

    public function testSetWeatherDataAppliesAllFields(): void
    {
        $data = new WeatherData();
        $data->InternalConditionId = WeatherConditionProfile::CLOUDY;
        $data->Temperature = 10;
        $data->AirPressure = 1033;
        $data->Humidity = 86;
        $data->WindSpeed = 25;
        $data->WindDirection = 302;
        $data->Source = WeatherSourceProfile::OPEN_WEATHER_MAP;

        $this->weathercache->setWeatherData($data);

        $this->assertSame(WeatherConditionProfile::CLOUDY, $this->weathercache->getWeatherid());
        $this->assertSame(10, $this->weathercache->getTemperature());
        $this->assertSame(1033, $this->weathercache->getPressure());
        $this->assertSame(86, $this->weathercache->getHumidity());
        $this->assertSame(25, $this->weathercache->getWindSpeed());
        $this->assertSame(302, $this->weathercache->getWindDeg());
        $this->assertSame(WeatherSourceProfile::OPEN_WEATHER_MAP, $this->weathercache->getWeatherSource());
    }

    public function testSetLocationStoresTruncatedGeohashAndTimestamp(): void
    {
        $location = new Location();
        $location->setPosition(52.52, 13.41);
        $location->setDateTime((new \DateTime())->setTimestamp(1234567890));

        $this->weathercache->setLocation($location);

        $this->assertSame(substr((string) $location->getGeohash(), 0, Weathercache::GEOHASH_PRECISION_DATABASE), $this->weathercache->getGeohash());
        $this->assertSame(1234567890, $this->weathercache->getTime());
    }

    public function testSetLocationThrowsWhenLocationHasNoPosition(): void
    {
        $location = new Location();
        $location->setDateTime(new \DateTime());

        $this->expectException(\InvalidArgumentException::class);

        $this->weathercache->setLocation($location);
    }

    public function testSetLocationThrowsWhenLocationHasNoDateTime(): void
    {
        $location = new Location();
        $location->setPosition(52.52, 13.41);

        $this->expectException(\InvalidArgumentException::class);

        $this->weathercache->setLocation($location);
    }
}
