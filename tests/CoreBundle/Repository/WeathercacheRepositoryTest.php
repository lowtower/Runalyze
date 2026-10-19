<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Repository;

use Runalyze\Bundle\CoreBundle\Entity\Weathercache;
use Runalyze\Bundle\CoreBundle\Repository\WeathercacheRepository;
use Runalyze\Profile\Weather\Source\WeatherSourceProfile;
use Runalyze\Profile\Weather\WeatherConditionProfile;
use Runalyze\Service\WeatherForecast\Location;

#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
class WeathercacheRepositoryTest extends AbstractRepositoryTestCase
{
    protected WeathercacheRepository $Repository;

    protected Location $Location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Repository = $this->getEntityManager()
            ->getRepository(Weathercache::class);

        $this->Location = new Location();
        $this->Location->setGeohash('u1xjn3n74zxv');
        $this->Location->setDateTime(new \DateTime()->setTimestamp(1234567890));
    }

    protected function insertExampleWeatherData()
    {
        $cache = new Weathercache();
        $cache->setGeohash('u1xjn3n74zxv');
        $cache->setTime(1234567890);
        $cache->setTemperature(10);
        $cache->setWindSpeed(25);
        $cache->setWindDeg(302);
        $cache->setHumidity(86);
        $cache->setPressure(1033);
        $cache->setWeatherid(WeatherConditionProfile::CLOUDY);
        $cache->setWeatherSource(WeatherSourceProfile::OPEN_WEATHER_MAP);

        $this->Repository->save($cache);
    }

    public function testEmptyCache(): void
    {
        $this->assertNull($this->Repository->getCachedWeatherDataFor($this->Location, 1800));
    }

    public function testFetchingCachedWeatherData(): void
    {
        $this->insertExampleWeatherData();

        $result = $this->Repository->getCachedWeatherDataFor($this->Location, 1800);

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $result);
        $this->assertEquals(10, $result->Temperature);
        $this->assertEquals(25, $result->WindSpeed);
        $this->assertSame(302, $result->WindDirection);
        $this->assertSame(86, $result->Humidity);
        $this->assertEquals(1033, $result->AirPressure);
        $this->assertSame(WeatherConditionProfile::CLOUDY, $result->InternalConditionId);
        $this->assertSame(WeatherSourceProfile::OPEN_WEATHER_MAP, $result->Source);
    }

    public function testWrongGeohashOrTime(): void
    {
        $this->insertExampleWeatherData();

        $this->Location->setGeohash('u1xm2f8jc');

        $this->assertNull($this->Repository->getCachedWeatherDataFor($this->Location, 1800));

        $this->Location->setGeohash('u1xjn3n74zxv');
        $this->Location->setDateTime(new \DateTime()->setTimestamp(9876543210));

        $this->assertNull($this->Repository->getCachedWeatherDataFor($this->Location, 1800));
    }

    public function testTimeTolerance(): void
    {
        $this->insertExampleWeatherData();

        $this->Location->setDateTime(new \DateTime()->setTimestamp(1234567890 - 1800 + 1));

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $this->Repository->getCachedWeatherDataFor($this->Location, 1800));

        $this->Location->setDateTime(new \DateTime()->setTimestamp(1234567890 + 1800 - 1));

        $this->assertInstanceOf(\Runalyze\Parser\Activity\Common\Data\WeatherData::class, $this->Repository->getCachedWeatherDataFor($this->Location, 1800));
        $this->assertNull($this->Repository->getCachedWeatherDataFor($this->Location, 1700));
    }
}
