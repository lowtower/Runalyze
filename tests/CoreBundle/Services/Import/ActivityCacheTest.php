<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityCacheTest extends TestCase
{
    protected ActivityCache $Cache;

    protected function setUp(): void
    {
        $this->Cache = new ActivityCache(
            new ArrayAdapter()
        );
    }

    public function testEmptyCache(): void
    {
        $this->assertNull($this->Cache->get('foobar'));
    }

    public function testGetWithNullHashReturnsTheGivenMergeTargetUnchanged(): void
    {
        $activityToMerge = (new Training())->setDistance(5.0);

        $this->assertSame($activityToMerge, $this->Cache->get(null, $activityToMerge));
    }

    public function testGetWithEmptyStringHashReturnsTheGivenMergeTargetUnchanged(): void
    {
        $activityToMerge = (new Training())->setDistance(5.0);

        $this->assertSame($activityToMerge, $this->Cache->get('', $activityToMerge));
    }

    public function testGetWithUnknownHashAndNoMergeTargetReturnsNull(): void
    {
        $this->assertNull($this->Cache->get('unknown-hash'));
    }

    public function testGetWithUnknownHashFallsBackToTheGivenMergeTarget(): void
    {
        $activityToMerge = (new Training())->setDistance(5.0);

        $this->assertSame($activityToMerge, $this->Cache->get('unknown-hash', $activityToMerge));
    }

    public function testCachingSimpleActivity(): void
    {
        $activity = new Training();
        $activity->setDistance(10.0);
        $activity->setS(3476);

        $hash = $this->Cache->save($activity);

        $this->assertEquals($activity, $this->Cache->get($hash));
    }

    public function testSaveProducesDifferentHashesForDifferentActivities(): void
    {
        $hashA = $this->Cache->save(new Training());
        $hashB = $this->Cache->save(new Training());

        $this->assertNotSame($hashA, $hashB);
    }

    public function testRemoveDeletesTheCachedActivity(): void
    {
        $hash = $this->Cache->save(new Training());
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $this->Cache->get($hash));

        $this->Cache->remove($hash);

        $this->assertNull($this->Cache->get($hash));
    }

    public function testGetWithRemoveAfterwardsDeletesTheCachedActivityOnFirstRetrieval(): void
    {
        $hash = $this->Cache->save(new Training());

        $firstRetrieval = $this->Cache->get($hash, null, true);
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $firstRetrieval);

        $this->assertNull($this->Cache->get($hash));
    }

    public function testSimpleMerge(): void
    {
        $activityToCache = new Training();
        $activityToCache->setDistance(10.0);
        $activityToCache->setElapsedTime(3625);
        $activityToCache->setAccount((new Account())->setName('tester'));
        $activityToCache->setRoute((new Route())->setDistance(10.0));
        $activityToCache->setTrackdata((new Trackdata())->setDistance([0.0, 5.0, 10.0]));
        $activityToCache->setSwimdata((new Swimdata())->setPoolLength(5000));
        $activityToCache->setHrv((new Hrv())->setData([820, 800, 850]));
        $activityToCache->setRaceresult((new Raceresult())->setName('Foobar event'));

        $mergerAccount = (new Account())->setName('merger');
        $activityToMerge = new Training();
        $activityToMerge->setAccount($mergerAccount);
        $activityToMerge->setDistance(12.3);

        $result = $this->Cache->get($this->Cache->save($activityToCache), $activityToMerge);

        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Entity\Training::class, $result);
        $this->assertSame(12.3, $result->getDistance());
        $this->assertSame(3625, $result->getElapsedTime());
        $this->assertEquals($mergerAccount, $result->getAccount());
        $this->assertEquals($mergerAccount, $result->getRoute()->getAccount());
        $this->assertEquals($mergerAccount, $result->getTrackdata()->getAccount());
        $this->assertEquals($mergerAccount, $result->getSwimdata()->getAccount());
        $this->assertEquals($mergerAccount, $result->getHrv()->getAccount());
        $this->assertEquals($mergerAccount, $result->getRaceresult()->getAccount());

        $this->assertSame(10.0, $result->getRoute()->getDistance());
        $this->assertEquals([0.0, 5.0, 10.0], $result->getTrackdata()->getDistance());
        $this->assertSame(5000, $result->getSwimdata()->getPoolLength());
        $this->assertEquals([820, 800, 850], $result->getHrv()->getData());
        $this->assertSame('Foobar event', $result->getRaceresult()->getName());
    }

    public function testMergeTransfersPowerWhenCachedActivityHasPowerCalculatedFlagSet(): void
    {
        $activityToCache = (new Training())
            ->setPower(250)
            ->setPowerCalculated(true);

        $activityToMerge = (new Training())->setPower(999);

        $result = $this->Cache->get($this->Cache->save($activityToCache), $activityToMerge);

        $this->assertSame(250, $result->getPower());
    }

    public function testMergeDoesNotOverwritePowerWhenCachedActivityHasNoPowerCalculatedFlag(): void
    {
        $activityToCache = (new Training())->setPower(250);
        $this->assertNull($activityToCache->isPowerCalculated());

        $activityToMerge = (new Training())->setPower(999);

        $result = $this->Cache->get($this->Cache->save($activityToCache), $activityToMerge);

        $this->assertSame(999, $result->getPower());
    }
}
