<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Entity\Adapter;

use Runalyze\Bundle\CoreBundle\Entity\Adapter\ActivityAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Profile\Athlete\Gender;
use Runalyze\Profile\FitSdk\StrokeTypeProfile;
use Runalyze\Util\LocalTime;

final class ActivityAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected Training $Activity;

    protected ActivityAdapter $Adapter;

    protected function setUp(): void
    {
        $this->Activity = new Training();
        $this->Adapter = new ActivityAdapter($this->Activity);
    }

    public function testEmptyActivity(): void
    {
        $this->assertFalse($this->Adapter->isRunning());
        $this->assertFalse($this->Adapter->isCycling());
        $this->assertFalse($this->Adapter->isSwimming());
        $this->assertFalse($this->Adapter->isRelevantForCurrentEffectiveVO2maxShape(1));
        $this->assertFalse($this->Adapter->isRelevantForCurrentMarathonShape(1));
    }

    public function testCalculationsForEmptyActivity(): void
    {
        $this->Adapter->calculatePower();
        $this->Adapter->calculateClimbScore();
        $this->Adapter->calculateValuesForSwimming();
        $this->Adapter->calculateIfActivityWasAtNight();
        $this->Adapter->calculateTrimp(Gender::NONE, 200, 60);

        $this->assertNull($this->Activity->getPower());
        $this->assertNull($this->Activity->isPowerCalculated());
        $this->assertNull($this->Activity->getClimbScore());
        $this->assertNull($this->Activity->getPercentageFlat());
        $this->assertNull($this->Activity->getSwolf());
        $this->assertNull($this->Activity->getTotalStrokes());
        $this->assertNull($this->Activity->isNight());
        $this->assertSame(0, $this->Activity->getTrimp());
    }

    public function testAgeOfActivity(): void
    {
        $this->Activity->setTime(new LocalTime()->sub(new \DateInterval('P2D'))->getTimestamp());

        $this->assertFalse($this->Adapter->isNotOlderThanXDays(1));
        $this->assertTrue($this->Adapter->isNotOlderThanXDays(2));
    }

    public function testAgeOfActivityForNow(): void
    {
        $this->Activity->setTime(LocalTime::now());

        $this->assertEqualsWithDelta(0, $this->Adapter->getAgeOfActivity(), 1);
        $this->assertTrue($this->Adapter->isNotOlderThanXDays(0));
    }

    public function testCalculationOfSwimmingValues(): void
    {
        $this->Activity->setS(323);

        $this->Activity->setSwimdata(new Swimdata());
        $this->Activity->getSwimdata()
            ->setStroke([32, 27, 28, 25]);
        $this->Activity->getSwimdata()
            ->setStroketype([
                StrokeTypeProfile::FREESTYLE,
                StrokeTypeProfile::FREESTYLE,
                StrokeTypeProfile::FREESTYLE,
                StrokeTypeProfile::FREESTYLE,
            ]);

        $this->Adapter->calculateValuesForSwimming();

        $this->assertSame(112, $this->Activity->getTotalStrokes());
        $this->assertSame(109, $this->Activity->getSwolf());
    }

    public function testCalculationOfSwimmingValuesIgnoresBreaksForSwolf(): void
    {
        $this->Activity->setS(323);

        $this->Activity->setSwimdata(new Swimdata());
        $this->Activity->getSwimdata()
            ->setStroke([32, 0, 27, 28, 25]);
        $this->Activity->getSwimdata()
            ->setStroketype([
                StrokeTypeProfile::FREESTYLE,
                StrokeTypeProfile::BREAK,
                StrokeTypeProfile::FREESTYLE,
                StrokeTypeProfile::FREESTYLE,
                StrokeTypeProfile::FREESTYLE,
            ]);

        $this->Adapter->calculateValuesForSwimming();

        $this->assertSame(112, $this->Activity->getTotalStrokes());
        $this->assertSame(109, $this->Activity->getSwolf());
    }

    public function testSwolfIsNullIfOnlyBreakLapsExist(): void
    {
        $this->Activity->setS(300);

        $this->Activity->setSwimdata(new Swimdata());
        $this->Activity->getSwimdata()
            ->setStroke([0, 0, 0]);
        $this->Activity->getSwimdata()
            ->setStroketype([
                StrokeTypeProfile::BREAK,
                StrokeTypeProfile::BREAK,
                StrokeTypeProfile::BREAK,
            ]);

        $this->Adapter->calculateSwolf();

        $this->assertNull($this->Activity->getSwolf());
    }

    public function testCalculationOfSwolfReturnsNullForEmptyTrackdataTime(): void
    {
        $this->Activity->setTrackdata(new Trackdata());
        $this->Activity->getTrackdata()
            ->setTime([]);

        $this->Activity->setSwimdata(new Swimdata());
        $this->Activity->getSwimdata()
            ->setStroke([32, 27]);

        $this->Adapter->calculateValuesForSwimming();

        $this->assertSame(59, $this->Activity->getTotalStrokes());
        $this->assertNull($this->Activity->getSwolf());
    }

    public function testCalculationOfEnergyConsumption(): void
    {
        $this->Activity->setSport(new Sport());
        $this->Activity->getSport()
            ->setKcal(720);
        $this->Activity->setS(1800);

        $this->Adapter->calculateEnergyConsumptionIfEmpty();

        $this->assertSame(360, $this->Activity->getKcal());

        $this->Activity->setKcal(371);

        $this->Adapter->calculateEnergyConsumptionIfEmpty();

        $this->assertSame(371, $this->Activity->getKcal());
    }

    public function testCalculationOfEnergyConsumptionWithoutSportDoesNotCrash(): void
    {
        $this->Activity->setS(1800);

        $this->Adapter->calculateEnergyConsumptionIfEmpty();

        $this->assertNull(
            $this->Activity->getKcal(),
            'Without a sport, energy consumption cannot be calculated - '
            . 'getKcal() should remain null (its default), not crash.'
        );
    }

    public function testCalculationOfClimbScore(): void
    {
        $this->Activity->setTrackdata(new Trackdata());
        $this->Activity->getTrackdata()
            ->setDistance([0.0, 1.0, 2.0, 3.0, 4.0, 5.0]);

        $this->Activity->setRoute(new Route());
        $this->Activity->getRoute()
            ->setElevationsCorrected([0, 0, 100, 250, 250, 250]);

        $this->Adapter->calculateClimbScore();

        $this->assertEqualsWithDelta(0.4, $this->Activity->getPercentageHilly(), PHP_FLOAT_EPSILON);
        $this->assertGreaterThan(0.0, $this->Activity->getClimbScore());
        $this->assertLessThan(10.0, $this->Activity->getClimbScore());

        $this->assertEquals([0, 0, 100, 250, 250, 250], $this->Activity->getRoute()->getElevationsCorrected());
    }

    public function testCalculationOfClimbScoreForStepwiseElevationProfile(): void
    {
        $this->Activity->setTrackdata(new Trackdata());
        $this->Activity->getTrackdata()
            ->setDistance(range(0.0, 29.0, 1.0));

        $this->Activity->setRoute(new Route());
        $this->Activity->getRoute()
            ->setElevationsCorrected(
                array_fill(0, 10, 0) +
                        array_fill(10, 10, 500) +
                        array_fill(20, 10, 0)
            );

        $backupElevations = $this->Activity->getRoute()
            ->getElevationsCorrected();

        $this->Adapter->calculateClimbScore();

        $this->assertGreaterThan(0.62, $this->Activity->getPercentageHilly());
        $this->assertLessThan(0.72, $this->Activity->getPercentageHilly());
        $this->assertGreaterThan(0.0, $this->Activity->getClimbScore());
        $this->assertLessThan(10.0, $this->Activity->getClimbScore());

        $this->assertEquals($backupElevations, $this->Activity->getRoute()->getElevationsCorrected());
    }
}
