<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Service\RouteNameEvaluation\RouteNameEval;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalResult;

class ActivityContextAdapterTest extends TestCase
{
    private function createAdapter(
        ActivityContext $context,
        ?WeatherForecast $weatherForecast = null,
        ?DuplicateFinder $duplicateFinder = null,
        ?RouteNameEval $routeNameEval = null
    ): ActivityContextAdapter {
        return new ActivityContextAdapter(
            $context,
            $weatherForecast ?? $this->createMock(WeatherForecast::class),
            $duplicateFinder ?? $this->createMock(DuplicateFinder::class),
            $routeNameEval ?? $this->createMock(RouteNameEval::class)
        );
    }

    public function testGuessWeatherConditionsDoesNothingWhenSportIsNotOutside(): void
    {
        $sport = $this->createMock(Sport::class);
        $sport->method('getOutside')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('getSport')
            ->willReturn($sport);
        $activity->expects($this->never())
            ->method('getTime');
        $activity->expects($this->never())
            ->method('getElapsedTime');

        $context = new ActivityContext($activity);

        $weatherForecast = $this->createMock(WeatherForecast::class);
        $weatherForecast->expects($this->never())
            ->method('loadForecast');

        $adapter = $this->createAdapter($context, $weatherForecast);

        $account = $this->createMock(Account::class);

        $adapter->guessWeatherConditions('Wolfsburg', $account);
    }

    public function testGuessRouteDetailsReturnsFalseWhenActivityHasNoRoute(): void
    {
        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(false);
        $activity->expects($this->never())
            ->method('setRouteName');
        $activity->expects($this->never())
            ->method('setNotes');

        $context = new ActivityContext($activity);

        $routeNameEval = $this->createMock(RouteNameEval::class);
        $routeNameEval->expects($this->never())
            ->method('evaluate');

        $adapter = $this->createAdapter($context, null, null, $routeNameEval);

        $this->assertFalse($adapter->guessRouteDetails());
    }

    public function testGuessRouteDetailsReturnsFalseWhenRouteHasNoGeohashes(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('hasGeohashes')
            ->willReturn(false);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->expects($this->never())
            ->method('setRouteName');

        $context = new ActivityContext($activity);

        $routeNameEval = $this->createMock(RouteNameEval::class);
        $routeNameEval->expects($this->never())
            ->method('evaluate');

        $adapter = $this->createAdapter($context, null, null, $routeNameEval);

        $this->assertFalse($adapter->guessRouteDetails());
    }

    public function testGuessRouteDetailsReturnsFalseWhenEvaluationYieldsNoResult(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('hasGeohashes')
            ->willReturn(true);

        $sport = $this->createMock(Sport::class);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getSport')
            ->willReturn($sport);
        $activity->method('getDistance')
            ->willReturn(10.0);
        $activity->expects($this->never())
            ->method('setRouteName');
        $activity->expects($this->never())
            ->method('setNotes');

        $context = new ActivityContext($activity);

        $routeNameEval = $this->createMock(RouteNameEval::class);
        $routeNameEval->method('evaluate')
            ->with($sport, $route, 10.0)
            ->willReturn(null);

        $adapter = $this->createAdapter($context, null, null, $routeNameEval);

        $this->assertFalse($adapter->guessRouteDetails());
    }

    public function testGuessRouteDetailsSetsRouteNameAndNotesWhenEvaluationSucceeds(): void
    {
        $route = $this->createMock(Route::class);
        $route->method('hasGeohashes')
            ->willReturn(true);

        $sport = $this->createMock(Sport::class);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getSport')
            ->willReturn($sport);
        $activity->method('getDistance')
            ->willReturn(21.1);
        $activity->method('getNotes')
            ->willReturn('existing note');

        $activity->expects($this->once())
            ->method('setRouteName')
            ->with('Nice Trail Names');

        $activity->expects($this->once())
            ->method('setNotes')
            ->with('existing note + auto notes');

        $context = new ActivityContext($activity);

        $result = $this->createMock(RouteNameEvalResult::class);
        $result->method('getNames')
            ->willReturn('Nice Trail Names');
        $result->method('appendNotes')
            ->with('existing note')
            ->willReturn('existing note + auto notes');

        $routeNameEval = $this->createMock(RouteNameEval::class);
        $routeNameEval->method('evaluate')
            ->with($sport, $route, 21.1)
            ->willReturn($result);

        $adapter = $this->createAdapter($context, null, null, $routeNameEval);

        $this->assertTrue($adapter->guessRouteDetails());
    }

    public function testIsPossibleDuplicateDelegatesToDuplicateFinder(): void
    {
        $activity = $this->createMock(Training::class);
        $context = new ActivityContext($activity);

        $duplicateFinder = $this->createMock(DuplicateFinder::class);
        $duplicateFinder->expects($this->once())
            ->method('isPossibleDuplicate')
            ->with($activity)
            ->willReturn(true);

        $adapter = $this->createAdapter($context, null, $duplicateFinder);

        $this->assertTrue($adapter->isPossibleDuplicate());
    }

    public function testIsPossibleDuplicateReturnsFalseWhenFinderReportsNoMatch(): void
    {
        $activity = $this->createMock(Training::class);
        $context = new ActivityContext($activity);

        $duplicateFinder = $this->createMock(DuplicateFinder::class);
        $duplicateFinder->method('isPossibleDuplicate')
            ->with($activity)
            ->willReturn(false);

        $adapter = $this->createAdapter($context, null, $duplicateFinder);

        $this->assertFalse($adapter->isPossibleDuplicate());
    }
}
