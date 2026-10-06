<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Hrv;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Swimdata;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;

class ActivityContextTest extends TestCase
{
    private function createTraining(): Training
    {
        return $this->createMock(Training::class);
    }

    public function testGetActivityReturnsInjectedTraining(): void
    {
        $activity = $this->createTraining();
        $context = new ActivityContext($activity);

        $this->assertSame($activity, $context->getActivity());
    }

    public function testGetAccountDelegatesToActivity(): void
    {
        $account = $this->createStub(Account::class);
        $activity = $this->createTraining();
        $activity->method('getAccount')
            ->willReturn($account);

        $context = new ActivityContext($activity);

        $this->assertSame($account, $context->getAccount());
    }

    public function testGetSportDelegatesToActivity(): void
    {
        $sport = $this->createStub(Sport::class);
        $activity = $this->createTraining();
        $activity->method('getSport')
            ->willReturn($sport);

        $context = new ActivityContext($activity);

        $this->assertSame($sport, $context->getSport());
    }

    public function testOptionalDependenciesDefaultToAbsent(): void
    {
        $context = new ActivityContext($this->createTraining());

        $this->assertNull($context->getTrackdata());
        $this->assertFalse($context->hasTrackdata());

        $this->assertNull($context->getSwimdata());
        $this->assertFalse($context->hasSwimdata());

        $this->assertNull($context->getRoute());
        $this->assertFalse($context->hasRoute());

        $this->assertNull($context->getHrv());
        $this->assertFalse($context->hasHRV());

        $this->assertNull($context->getRaceResult());
        $this->assertFalse($context->hasRaceResult());

        $this->assertFalse($context->canShowMap());
    }

    public function testTrackdataIsExposedWhenProvided(): void
    {
        $trackdata = $this->createStub(Trackdata::class);
        $context = new ActivityContext($this->createTraining(), $trackdata);

        $this->assertSame($trackdata, $context->getTrackdata());
        $this->assertTrue($context->hasTrackdata());
    }

    public function testSwimdataIsExposedWhenProvided(): void
    {
        $swimdata = $this->createStub(Swimdata::class);
        $context = new ActivityContext($this->createTraining(), null, $swimdata);

        $this->assertSame($swimdata, $context->getSwimdata());
        $this->assertTrue($context->hasSwimdata());
    }

    public function testHrvIsExposedWhenProvided(): void
    {
        $hrv = $this->createStub(Hrv::class);
        $context = new ActivityContext($this->createTraining(), null, null, null, $hrv);

        $this->assertSame($hrv, $context->getHrv());
        $this->assertTrue($context->hasHRV());
    }

    public function testRaceResultIsExposedWhenProvided(): void
    {
        $raceResult = $this->createStub(Raceresult::class);
        $context = new ActivityContext($this->createTraining(), null, null, null, null, $raceResult);

        $this->assertSame($raceResult, $context->getRaceResult());
        $this->assertTrue($context->hasRaceResult());
    }

    public function testCanShowMapIsTrueOnlyWithRouteAndGeohashes(): void
    {
        $routeWithGeohashes = $this->createMock(Route::class);
        $routeWithGeohashes->method('hasGeohashes')
            ->willReturn(true);

        $context = new ActivityContext($this->createTraining(), null, null, $routeWithGeohashes);

        $this->assertTrue($context->hasRoute());
        $this->assertTrue($context->canShowMap());
    }

    public function testCanShowMapIsFalseWhenRouteHasNoGeohashes(): void
    {
        $routeWithoutGeohashes = $this->createMock(Route::class);
        $routeWithoutGeohashes->method('hasGeohashes')
            ->willReturn(false);

        $context = new ActivityContext($this->createTraining(), null, null, $routeWithoutGeohashes);

        $this->assertTrue($context->hasRoute());
        $this->assertFalse($context->canShowMap());
    }

    public function testGetDecoratorReturnsSameCachedInstance(): void
    {
        $context = new ActivityContext($this->createTraining());

        $decorator = $context->getDecorator();

        $this->assertInstanceOf(ActivityDecorator::class, $decorator);
        $this->assertSame($decorator, $context->getDecorator());
    }
}
