<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Bridge\Activity\Calculation;

use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\NightDetector;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Util\LocalTime;

final class NightDetectorTest extends \PHPUnit\Framework\TestCase
{
    protected NightDetector $Detector;

    protected Training $Activity;

    protected function setUp(): void
    {
        $this->Activity = new Training();
        $this->Activity->setRoute(new Route());

        $this->Detector = new NightDetector();
    }

    public function testEmptyActivity(): void
    {
        $this->assertNull($this->Detector->isActivityAtNight($this->Activity));
    }

    public function testActivityWithoutRoute(): void
    {
        $this->Activity->setRoute();

        $this->assertNull($this->Detector->isActivityAtNight($this->Activity));
    }

    public function testSomeValuesFromCentralEurope(): void
    {
        $this->checkData([
            ['2016-01-13 08:00:00', [49.44, 7.45], true], // Kaiserslautern, de
            ['2016-01-13 17:00:00', [49.44, 7.45], true],
            ['2016-01-21 17:00:00', [49.44, 7.45], false],
            ['2016-02-03 08:00:00', [49.44, 7.45], false],
            ['2016-03-26 07:00:00', [49.44, 7.45], false],
            ['2016-03-26 19:00:00', [49.44, 7.45], true],
            ['2016-03-27 07:00:00', [49.44, 7.45], true], // summertime
            ['2016-03-27 19:00:00', [49.44, 7.45], false],
            ['2016-10-29 08:00:00', [49.44, 7.45], true],
            ['2016-10-29 18:00:00', [49.44, 7.45], false],
            ['2016-10-30 08:00:00', [49.44, 7.45], false], // wintertime
            ['2016-10-30 18:00:00', [49.44, 7.45], true],
            ['2016-10-29 08:00:00 Europe/Berlin', [49.44, 7.45], true], // with explicit central europe time zone
            ['2016-10-29 18:00:00 Europe/Berlin', [49.44, 7.45], false],
            ['2016-10-30 08:00:00 Europe/Berlin', [49.44, 7.45], false], // wintertime
            ['2016-10-30 18:00:00 Europe/Berlin', [49.44, 7.45], true],
        ]);
    }

    /**
     * @param array<int, array<int, float[]>>|array<int, string[]>|array<int, bool[]> $testData [['time string', [lat, lng], true|false], ...]
     */
    protected function checkData(array $testData): void
    {
        $this->Activity->getRoute()
            ->setGeohashes([]);

        foreach ($testData as $data) {
            /** @var Geohash $geohashObj */
            $geohashObj = new Geohash()->encode(new Coordinate($data[1]));
            // Check the actual return type and available methods$this->Activity->getRoute()->setStartpoint($geohash->getHash()); // or similar
            $this->Activity->setTime(LocalTime::fromServerTime(strtotime($data[0]))->getTimestamp());
            $this->Activity->getRoute()
                ->setStartpoint($geohashObj->getGeohash());

            $this->assertEquals(
                $data[2],
                $this->Detector->isActivityAtNight($this->Activity),
                'Tried to test ' . $data[0] . ' at ' . implode('/', $data[1])
            );
        }
    }

    public function testThatDurationIsRespected(): void
    {
        /** @var Geohash $geohashObj */
        $geohashObj = new Geohash()->encode(new Coordinate([49.44, 7.45]));
        $this->Activity->getRoute()
            ->setGeohashes([]);
        $this->Activity->setTime(LocalTime::fromServerTime(strtotime('2016-01-13 08:00:00'))->getTimestamp());
        $this->Activity->getRoute()
            ->setStartpoint($geohashObj->getGeohash());

        $this->assertTrue($this->Detector->isActivityAtNight($this->Activity));

        $this->Activity->setS(3600);

        $this->assertFalse($this->Detector->isActivityAtNight($this->Activity));
    }
}
