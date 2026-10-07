<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ClimbFinder;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ClimbScoreCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\Climb;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbCollection;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbProfile;
use Runalyze\Sports\ClimbQuantification\FietsIndex;

final class ClimbScoreCalculatorTest extends TestCase
{
    // Stub by default. Tests that need expects() on the ClimbFinder
    // build their own mock and their own calculator.
    private ClimbFinder&Stub $climbFinder;

    private FietsIndex $fietsIndex;

    private ClimbScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->climbFinder = $this->createStub(ClimbFinder::class);
        $this->fietsIndex = new FietsIndex();
        $this->calculator = new ClimbScoreCalculator($this->fietsIndex, $this->climbFinder);
    }

    private function configureTraining(Stub $training, bool $hasRoute, bool $hasTrackdata, ?Route $route, ?float $percentageFlat): void
    {
        $training->method('hasRoute')
            ->willReturn($hasRoute);
        $training->method('hasTrackdata')
            ->willReturn($hasTrackdata);
        $training->method('getRoute')
            ->willReturn($route);
        $training->method('getPercentageFlat')
            ->willReturn($percentageFlat);
    }

    private function createTraining(bool $hasRoute, bool $hasTrackdata, ?Route $route, ?float $percentageFlat): Training&Stub
    {
        $training = $this->createStub(Training::class);
        $this->configureTraining($training, $hasRoute, $hasTrackdata, $route, $percentageFlat);

        return $training;
    }

    /**
     * For tests that put expects() on the training itself.
     */
    private function createTrainingMock(bool $hasRoute, bool $hasTrackdata, ?Route $route, ?float $percentageFlat): Training&MockObject
    {
        $training = $this->createMock(Training::class);
        $this->configureTraining($training, $hasRoute, $hasTrackdata, $route, $percentageFlat);

        return $training;
    }

    private function createRoute(float $distance): Route&Stub
    {
        $route = $this->createStub(Route::class);
        $route->method('getDistance')
            ->willReturn($distance);

        return $route;
    }

    /**
     * @param float[] $profileDistances
     * @param int[] $profileElevations
     */
    private function createClimbWithProfile(
        float $distance,
        int $elevation,
        int $altitudeAtTop,
        array $profileDistances,
        array $profileElevations
    ): Climb {
        $climb = new Climb($distance, $elevation);
        $climb->setAltitudeAtTop($altitudeAtTop);
        $climb->setClimbProfile(new ClimbProfile($profileDistances, $profileElevations));

        return $climb;
    }

    public function testCalculateForSetsAnUnknownScoreWhenActivityHasNoRoute(): void
    {
        $activity = $this->createTrainingMock(false, true, null, null);
        $activity->expects(self::once())->method('setClimbScore')->with(null);

        $climbFinder = $this->createMock(ClimbFinder::class);
        $climbFinder->expects(self::never())->method('findClimbsFor');
        $calculator = new ClimbScoreCalculator($this->fietsIndex, $climbFinder);

        $score = $calculator->calculateFor($activity);

        self::assertFalse($score->isKnown());
        self::assertNull($score->getScore());
    }

    public function testCalculateForSetsAnUnknownScoreWhenActivityHasNoTrackdata(): void
    {
        $activity = $this->createTrainingMock(true, false, $this->createRoute(10.0), 0.0);
        $activity->expects(self::once())->method('setClimbScore')->with(null);

        $climbFinder = $this->createMock(ClimbFinder::class);
        $climbFinder->expects(self::never())->method('findClimbsFor');
        $calculator = new ClimbScoreCalculator($this->fietsIndex, $climbFinder);

        $score = $calculator->calculateFor($activity);

        self::assertFalse($score->isKnown());
    }

    public function testCalculateForReturnsAKnownButZeroScoreWhenThereAreNoClimbs(): void
    {
        $route = $this->createRoute(10.0);
        $activity = $this->createTrainingMock(true, true, $route, 1.0); // fully flat

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection());

        $activity->expects(self::once())->method('setClimbScore')->with(self::isFloat());

        $score = $this->calculator->calculateFor($activity);

        self::assertTrue($score->isKnown());
        self::assertSame(0.0, $score->getScore());
    }

    public function testCalculateForIgnoresClimbsBelowTheGradientThreshold(): void
    {
        $climb = $this->createClimbWithProfile(1.0, 5, 105, [1.0], [5]);

        $route = $this->createRoute(10.0);
        $activity = $this->createTraining(true, true, $route, 0.0);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection([$climb]));

        $score = $this->calculator->calculateFor($activity);

        $expected = 2.0 * log(1.5, 2.0);
        self::assertEqualsWithDelta($expected, $score->getScore(), 0.0001);
    }

    public function testCalculateForIgnoresClimbsAtOrBelowTheDistanceThreshold(): void
    {
        $climb = $this->createClimbWithProfile(0.1, 50, 150, [0.1], [50]);

        $route = $this->createRoute(10.0);
        $activity = $this->createTraining(true, true, $route, 0.0);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection([$climb]));

        $score = $this->calculator->calculateFor($activity);

        $expected = 2.0 * log(1.5, 2.0);
        self::assertEqualsWithDelta($expected, $score->getScore(), 0.0001);
    }

    public function testCalculateForUsesClimbProfileWhenAvailable(): void
    {
        $climb = $this->createClimbWithProfile(2.0, 100, 200, [1.0, 1.0], [50, 50]);

        $route = $this->createRoute(10.0);
        $activity = $this->createTraining(true, true, $route, 0.0);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection([$climb]));

        $score = $this->calculator->calculateFor($activity);

        self::assertTrue($score->isKnown());
        self::assertIsFloat($score->getScore());
    }

    public function testCalculateForDelegatesToFietsIndexGetScoreForProfileWhenClimbHasAProfile(): void
    {
        $mockedFietsIndex = $this->createMock(FietsIndex::class);
        $mockedFietsIndex
            ->expects(self::once())
            ->method('getScoreForProfile')
            ->with(self::isArray(), 200)
            ->willReturn(3.5);
        $mockedFietsIndex->expects(self::never())->method('getScoreFor');

        $calculator = new ClimbScoreCalculator($mockedFietsIndex, $this->climbFinder);

        $climb = $this->createClimbWithProfile(2.0, 100, 200, [1.0, 1.0], [50, 50]);
        $route = $this->createRoute(10.0);
        $activity = $this->createTraining(true, true, $route, 0.0);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection([$climb]));

        $calculator->calculateFor($activity);
    }

    public function testCalculateForFallsBackToGetScoreForWhenClimbHasNoProfile(): void
    {
        $climb = new Climb(1.0, 50);
        $climb->setAltitudeAtTop(150);

        $mockedFietsIndex = $this->createMock(FietsIndex::class);
        $mockedFietsIndex
            ->expects(self::once())
            ->method('getScoreFor')
            ->with(1.0, 50, 150)
            ->willReturn(2.0);
        $mockedFietsIndex->expects(self::never())->method('getScoreForProfile');

        $calculator = new ClimbScoreCalculator($mockedFietsIndex, $this->climbFinder);

        $route = $this->createRoute(10.0);
        $activity = $this->createTraining(true, true, $route, 0.0);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection([$climb]));

        $calculator->calculateFor($activity);
    }

    public function testCalculateForSumsScoresAcrossMultipleClimbs(): void
    {
        $mockedFietsIndex = $this->createStub(FietsIndex::class);
        $mockedFietsIndex
            ->method('getScoreForProfile')
            ->willReturnOnConsecutiveCalls(1.0, 2.0);

        $calculator = new ClimbScoreCalculator($mockedFietsIndex, $this->climbFinder);

        $climbA = $this->createClimbWithProfile(1.0, 50, 150, [1.0], [50]);
        $climbB = $this->createClimbWithProfile(1.0, 50, 150, [1.0], [50]);

        $route = $this->createRoute(20.0);
        $activity = $this->createTraining(true, true, $route, 0.0);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection([$climbA, $climbB]));

        $score = $calculator->calculateFor($activity);

        $expected = 2.0 * log(4.5, 2.0);
        self::assertEqualsWithDelta($expected, $score->getScore(), 0.0001);
    }

    public function testCalculateForSetsClimbScoreOnTheActivityItself(): void
    {
        $route = $this->createRoute(10.0);
        $activity = $this->createTrainingMock(true, true, $route, 0.5);

        $this->climbFinder->method('findClimbsFor')
            ->willReturn(new ClimbCollection());

        $capturedScore = null;
        $activity
            ->expects(self::once())
            ->method('setClimbScore')
            ->willReturnCallback(function (?float $score) use (&$capturedScore, $activity): Training {
                $capturedScore = $score;

                return $activity;
            });

        $score = $this->calculator->calculateFor($activity);

        self::assertSame($score->getScore(), $capturedScore);
    }
}
