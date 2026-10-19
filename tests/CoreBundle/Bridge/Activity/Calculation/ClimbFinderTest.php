<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ClimbFinder;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbCollection;

final class ClimbFinderTest extends TestCase
{
    private ClimbFinder $climbFinder;

    protected function setUp(): void
    {
        $this->climbFinder = new ClimbFinder();
    }

    private function createTraining(
        bool $hasRoute,
        ?Route $route,
        bool $hasTrackdata,
        ?Trackdata $trackdata
    ): Training&Stub {
        $training = $this->createStub(Training::class);
        $training->method('hasRoute')
            ->willReturn($hasRoute);
        $training->method('getRoute')
            ->willReturn($route);
        $training->method('hasTrackdata')
            ->willReturn($hasTrackdata);
        $training->method('getTrackdata')
            ->willReturn($trackdata);

        return $training;
    }

    private function createRoute(bool $hasElevations, ?array $elevations): Route&Stub
    {
        $route = $this->createStub(Route::class);
        $route->method('hasElevations')
            ->willReturn($hasElevations);
        $route->method('getElevations')
            ->willReturn($elevations);

        return $route;
    }

    private function createTrackdata(?array $distance): Trackdata&Stub
    {
        $trackdata = $this->createStub(Trackdata::class);
        $trackdata->method('getDistance')
            ->willReturn($distance);

        return $trackdata;
    }

    public function testReturnsEmptyCollectionWhenActivityHasNoRoute(): void
    {
        $training = $this->createTraining(false, null, true, $this->createTrackdata([0.0, 1.0]));

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertInstanceOf(ClimbCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testReturnsEmptyCollectionWhenRouteHasNoElevations(): void
    {
        $route = $this->createRoute(false, null);
        $training = $this->createTraining(true, $route, true, $this->createTrackdata([0.0, 1.0]));

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertTrue($result->isEmpty());
    }

    public function testReturnsEmptyCollectionWhenActivityHasNoTrackdata(): void
    {
        $route = $this->createRoute(true, [100, 110]);
        $training = $this->createTraining(true, $route, false, null);

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertTrue($result->isEmpty());
    }

    public function testReturnsEmptyCollectionWhenTrackdataDistanceIsNull(): void
    {
        $route = $this->createRoute(true, [100, 110]);
        $trackdata = $this->createTrackdata(null);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertTrue($result->isEmpty());
    }

    public function testFindClimbsForDetectsASingleClimbOnCollinearAscendingData(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [100, 110, 120, 130, 140];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertCount(1, $result);

        $climb = $result->getElements()[0];
        $this->assertEqualsWithDelta(4.0, $climb->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertSame(40, $climb->getElevation());
        $this->assertSame(140, $climb->getAltitudeAtTop());
        $this->assertSame(0, $climb->getTrackdataStartIndex());
        $this->assertSame(4, $climb->getTrackdataEndIndex());

        $profile = $climb->getClimbProfile();
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile::class, $profile);
        $this->assertCount(4, $profile);
        $this->assertSame([1.0, 1.0, 1.0, 1.0], $profile->getDistances());
        $this->assertSame([10, 10, 10, 10], $profile->getElevations());
    }

    public function testFindClimbsForReturnsEmptyCollectionOnCollinearDescendingData(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [140, 130, 120, 110, 100];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertTrue($result->isEmpty());
    }

    public function testFindClimbsForReturnsEmptyCollectionOnFlatCollinearData(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0];
        $elevations = [100, 100, 100, 100];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertTrue($result->isEmpty());
    }

    public function testFindClimbsForOnNonCollinearZigzagDataWithDefaultEpsilon(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [0, 10, 0, 10, 0];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        $this->assertCount(2, $result);

        $firstClimb = $result->getElements()[0];
        $this->assertEqualsWithDelta(1.0, $firstClimb->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertSame(10, $firstClimb->getElevation());
        $this->assertSame(0, $firstClimb->getTrackdataStartIndex());
        $this->assertSame(1, $firstClimb->getTrackdataEndIndex());

        $secondClimb = $result->getElements()[1];
        $this->assertEqualsWithDelta(1.0, $secondClimb->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertSame(10, $secondClimb->getElevation());
        $this->assertSame(2, $secondClimb->getTrackdataStartIndex());
        $this->assertSame(3, $secondClimb->getTrackdataEndIndex());
    }

    public function testFindClimbsForActuallyChangesResultWhenACustomEpsilonIsProvided(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [0, 10, 0, 10, 0];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training, 5.0);

        $this->assertCount(1, $result);

        $climb = $result->getElements()[0];
        $this->assertEqualsWithDelta(1.0, $climb->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertSame(10, $climb->getElevation());
        $this->assertSame(0, $climb->getTrackdataStartIndex());
        $this->assertSame(1, $climb->getTrackdataEndIndex());
    }

    /**
     * @param int[] $indices
     * @param float[] $distances
     * @param int[] $elevations
     */
    private function invokeCreateClimbsForIndices(array $indices, array $distances, array $elevations): ClimbCollection
    {
        $method = new \ReflectionMethod(ClimbFinder::class, 'createClimbsForIndices');

        return $method->invoke($this->climbFinder, $indices, $distances, $elevations);
    }

    public function testCreateClimbsForIndicesProducesOneClimbPerAscendingSegment(): void
    {
        $indices = [0, 2, 5, 7];
        $distances = [0.0, 0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5];
        $elevations = [100, 105, 110, 108, 106, 130, 140, 90];

        $result = $this->invokeCreateClimbsForIndices($indices, $distances, $elevations);

        $this->assertCount(2, $result);

        $firstClimb = $result->getElements()[0];
        $this->assertEqualsWithDelta(1.0, $firstClimb->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertSame(10, $firstClimb->getElevation());
        $this->assertSame(110, $firstClimb->getAltitudeAtTop());
        $this->assertSame(0, $firstClimb->getTrackdataStartIndex());
        $this->assertSame(2, $firstClimb->getTrackdataEndIndex());

        $firstProfile = $firstClimb->getClimbProfile();
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile::class, $firstProfile);
        $this->assertCount(2, $firstProfile);
        $this->assertSame([0.5, 0.5], $firstProfile->getDistances());
        $this->assertSame([5, 5], $firstProfile->getElevations());

        $secondClimb = $result->getElements()[1];
        $this->assertEqualsWithDelta(1.5, $secondClimb->getDistance(), PHP_FLOAT_EPSILON);
        $this->assertSame(20, $secondClimb->getElevation());
        $this->assertSame(130, $secondClimb->getAltitudeAtTop());
        $this->assertSame(2, $secondClimb->getTrackdataStartIndex());
        $this->assertSame(5, $secondClimb->getTrackdataEndIndex());

        $secondProfile = $secondClimb->getClimbProfile();
        $this->assertInstanceOf(\Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile::class, $secondProfile);
        $this->assertCount(3, $secondProfile);
        $this->assertSame([0.5, 0.5, 0.5], $secondProfile->getDistances());
        $this->assertSame([-2, -2, 24], $secondProfile->getElevations());
    }

    public function testCreateClimbsForIndicesSkipsSegmentWhereDistanceDoesNotIncrease(): void
    {
        $indices = [0, 1];
        $distances = [5.0, 5.0];
        $elevations = [100, 110];

        $result = $this->invokeCreateClimbsForIndices($indices, $distances, $elevations);

        $this->assertTrue($result->isEmpty());
    }

    public function testCreateClimbsForIndicesReturnsEmptyCollectionForSingleIndex(): void
    {
        $result = $this->invokeCreateClimbsForIndices([0], [0.0], [100]);

        $this->assertTrue($result->isEmpty());
    }
}
