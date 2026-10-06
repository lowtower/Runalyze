<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\MockObject;
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
    ): Training&MockObject {
        $training = $this->createMock(Training::class);
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

    private function createRoute(bool $hasElevations, ?array $elevations): Route&MockObject
    {
        $route = $this->createMock(Route::class);
        $route->method('hasElevations')
            ->willReturn($hasElevations);
        $route->method('getElevations')
            ->willReturn($elevations);

        return $route;
    }

    private function createTrackdata(?array $distance): Trackdata&MockObject
    {
        $trackdata = $this->createMock(Trackdata::class);
        $trackdata->method('getDistance')
            ->willReturn($distance);

        return $trackdata;
    }

    public function testReturnsEmptyCollectionWhenActivityHasNoRoute(): void
    {
        $training = $this->createTraining(false, null, true, $this->createTrackdata([0.0, 1.0]));

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertInstanceOf(ClimbCollection::class, $result);
        self::assertTrue($result->isEmpty());
    }

    public function testReturnsEmptyCollectionWhenRouteHasNoElevations(): void
    {
        $route = $this->createRoute(false, null);
        $training = $this->createTraining(true, $route, true, $this->createTrackdata([0.0, 1.0]));

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertTrue($result->isEmpty());
    }

    public function testReturnsEmptyCollectionWhenActivityHasNoTrackdata(): void
    {
        $route = $this->createRoute(true, [100, 110]);
        $training = $this->createTraining(true, $route, false, null);

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertTrue($result->isEmpty());
    }

    public function testReturnsEmptyCollectionWhenTrackdataDistanceIsNull(): void
    {
        $route = $this->createRoute(true, [100, 110]);
        $trackdata = $this->createTrackdata(null);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertTrue($result->isEmpty());
    }

    public function testFindClimbsForDetectsASingleClimbOnCollinearAscendingData(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [100, 110, 120, 130, 140];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertCount(1, $result);

        $climb = $result->getElements()[0];
        self::assertSame(4.0, $climb->getDistance());
        self::assertSame(40, $climb->getElevation());
        self::assertSame(140, $climb->getAltitudeAtTop());
        self::assertSame(0, $climb->getTrackdataStartIndex());
        self::assertSame(4, $climb->getTrackdataEndIndex());

        $profile = $climb->getClimbProfile();
        self::assertNotNull($profile);
        self::assertCount(4, $profile);
        self::assertSame([1.0, 1.0, 1.0, 1.0], $profile->getDistances());
        self::assertSame([10, 10, 10, 10], $profile->getElevations());
    }

    public function testFindClimbsForReturnsEmptyCollectionOnCollinearDescendingData(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [140, 130, 120, 110, 100];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertTrue($result->isEmpty());
    }

    public function testFindClimbsForReturnsEmptyCollectionOnFlatCollinearData(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0];
        $elevations = [100, 100, 100, 100];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertTrue($result->isEmpty());
    }

    public function testFindClimbsForOnNonCollinearZigzagDataWithDefaultEpsilon(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [0, 10, 0, 10, 0];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training);

        self::assertCount(2, $result);

        $firstClimb = $result->getElements()[0];
        self::assertSame(1.0, $firstClimb->getDistance());
        self::assertSame(10, $firstClimb->getElevation());
        self::assertSame(0, $firstClimb->getTrackdataStartIndex());
        self::assertSame(1, $firstClimb->getTrackdataEndIndex());

        $secondClimb = $result->getElements()[1];
        self::assertSame(1.0, $secondClimb->getDistance());
        self::assertSame(10, $secondClimb->getElevation());
        self::assertSame(2, $secondClimb->getTrackdataStartIndex());
        self::assertSame(3, $secondClimb->getTrackdataEndIndex());
    }

    public function testFindClimbsForActuallyChangesResultWhenACustomEpsilonIsProvided(): void
    {
        $distances = [0.0, 1.0, 2.0, 3.0, 4.0];
        $elevations = [0, 10, 0, 10, 0];

        $route = $this->createRoute(true, $elevations);
        $trackdata = $this->createTrackdata($distances);
        $training = $this->createTraining(true, $route, true, $trackdata);

        $result = $this->climbFinder->findClimbsFor($training, 5.0);

        self::assertCount(1, $result);

        $climb = $result->getElements()[0];
        self::assertSame(1.0, $climb->getDistance());
        self::assertSame(10, $climb->getElevation());
        self::assertSame(0, $climb->getTrackdataStartIndex());
        self::assertSame(1, $climb->getTrackdataEndIndex());
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

        self::assertCount(2, $result);

        $firstClimb = $result->getElements()[0];
        self::assertSame(1.0, $firstClimb->getDistance());
        self::assertSame(10, $firstClimb->getElevation());
        self::assertSame(110, $firstClimb->getAltitudeAtTop());
        self::assertSame(0, $firstClimb->getTrackdataStartIndex());
        self::assertSame(2, $firstClimb->getTrackdataEndIndex());

        $firstProfile = $firstClimb->getClimbProfile();
        self::assertSame(2, $firstProfile->count());
        self::assertSame([0.5, 0.5], $firstProfile->getDistances());
        self::assertSame([5, 5], $firstProfile->getElevations());

        $secondClimb = $result->getElements()[1];
        self::assertSame(1.5, $secondClimb->getDistance());
        self::assertSame(20, $secondClimb->getElevation());
        self::assertSame(130, $secondClimb->getAltitudeAtTop());
        self::assertSame(2, $secondClimb->getTrackdataStartIndex());
        self::assertSame(5, $secondClimb->getTrackdataEndIndex());

        $secondProfile = $secondClimb->getClimbProfile();
        self::assertSame(3, $secondProfile->count());
        self::assertSame([0.5, 0.5, 0.5], $secondProfile->getDistances());
        self::assertSame([-2, -2, 24], $secondProfile->getElevations());
    }

    public function testCreateClimbsForIndicesSkipsSegmentWhereDistanceDoesNotIncrease(): void
    {
        $indices = [0, 1];
        $distances = [5.0, 5.0];
        $elevations = [100, 110];

        $result = $this->invokeCreateClimbsForIndices($indices, $distances, $elevations);

        self::assertTrue($result->isEmpty());
    }

    public function testCreateClimbsForIndicesReturnsEmptyCollectionForSingleIndex(): void
    {
        $result = $this->invokeCreateClimbsForIndices([0], [0.0], [100]);

        self::assertTrue($result->isEmpty());
    }
}
