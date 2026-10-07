<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Util\LocalTime;

class ActivityDecoratorTest extends TestCase
{
    private function createDecorator(Training $activity, ?Route $route = null): ActivityDecorator
    {
        $context = new ActivityContext($activity, null, null, $route);

        return new ActivityDecorator($context);
    }

    private function createSport(string $name = 'Running'): Sport
    {
        $sport = $this->createStub(Sport::class);
        $sport->method('getName')
            ->willReturn($name);

        return $sport;
    }

    public function testGetTitleUsesCustomTitleWithSportPrefixWhenTitleIsSet(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getTitle')
            ->willReturn('Morning Run');
        $activity->method('getSport')
            ->willReturn($this->createSport('Running'));

        $decorator = $this->createDecorator($activity);

        $this->assertSame('Running: Morning Run', $decorator->getTitle(false));
        $this->assertSame('Running: Morning Run', $decorator->getTitle(true));
    }

    public function testGetTitleFallsBackToTypeNameWhenNoCustomTitleAndTypeWithSportIsFalse(): void
    {
        $type = $this->createStub(Type::class);
        $type->method('getName')
            ->willReturn('Interval');

        $activity = $this->createStub(Training::class);
        $activity->method('getTitle')
            ->willReturn('');
        $activity->method('getType')
            ->willReturn($type);
        $activity->method('getSport')
            ->willReturn($this->createSport('Running'));

        $decorator = $this->createDecorator($activity);

        $this->assertSame('Interval', $decorator->getTitle(false));
    }

    public function testGetTitlePrefixesTypeNameWithSportWhenTypeWithSportIsTrue(): void
    {
        $type = $this->createStub(Type::class);
        $type->method('getName')
            ->willReturn('Interval');

        $activity = $this->createStub(Training::class);
        $activity->method('getTitle')
            ->willReturn('');
        $activity->method('getType')
            ->willReturn($type);
        $activity->method('getSport')
            ->willReturn($this->createSport('Running'));

        $decorator = $this->createDecorator($activity);

        $this->assertSame('Running: Interval', $decorator->getTitle(true));
    }

    public function testGetTitleFallsBackToSportNameWhenNoCustomTitleAndNoType(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getTitle')
            ->willReturn('');
        $activity->method('getType')
            ->willReturn(null);
        $activity->method('getSport')
            ->willReturn($this->createSport('Cycling'));

        $decorator = $this->createDecorator($activity);

        $this->assertSame('Cycling', $decorator->getTitle(false));
        $this->assertSame('Cycling', $decorator->getTitle(true));
    }

    public function testGetDateTimeWrapsActivityTimeInLocalTime(): void
    {
        // Training::getTime() is declared as `int` (Unix timestamp), not
        // \DateTime - confirmed by PHPUnit's return-type check.
        $timestamp = new \DateTime('2026-05-01 07:30:00')->getTimestamp();

        $activity = $this->createStub(Training::class);
        $activity->method('getTime')
            ->willReturn($timestamp);

        $decorator = $this->createDecorator($activity);
        $result = $decorator->getDateTime();

        $this->assertInstanceOf(LocalTime::class, $result);
        $this->assertSame($timestamp, $result->getTimestamp());
    }

    public function testElevationFallsBackToActivityElevationWhenNoRoute(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getElevation')
            ->willReturn(123);

        $decorator = $this->createDecorator($activity);

        $this->assertSame(123, $decorator->getElevationUp());
        $this->assertSame(123, $decorator->getElevationDown());
    }

    public function testElevationFallsBackToActivityElevationWhenRouteHasNoElevationData(): void
    {
        $route = $this->createStub(Route::class);
        $route->method('getElevationUp')
            ->willReturn(0);
        $route->method('getElevationDown')
            ->willReturn(0);

        $activity = $this->createStub(Training::class);
        $activity->method('getElevation')
            ->willReturn(50);

        $decorator = $this->createDecorator($activity, $route);

        $this->assertSame(50, $decorator->getElevationUp());
        $this->assertSame(50, $decorator->getElevationDown());
    }

    public function testElevationUsesRouteValuesWhenRouteHasElevationData(): void
    {
        $route = $this->createStub(Route::class);
        $route->method('getElevationUp')
            ->willReturn(300);
        $route->method('getElevationDown')
            ->willReturn(280);

        // Stays a mock: it has an expectation (never).
        $activity = $this->createMock(Training::class);
        // Should not be used at all when the route provides elevation data.
        $activity->expects($this->never())
            ->method('getElevation');

        $decorator = $this->createDecorator($activity, $route);

        $this->assertSame(300, $decorator->getElevationUp());
        $this->assertSame(280, $decorator->getElevationDown());
    }

    public function testUncorrectedVO2maxUsesElevationCorrectedValueWhenEnabledAndPositive(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getVO2maxWithElevation')
            ->willReturn(55.5);
        $activity->method('getVO2max')
            ->willReturn(50.0);

        $config = $this->createStub(RunalyzeConfigurationList::class);
        $config->method('useVO2maxCorrectionForElevation')
            ->willReturn(true);

        $decorator = $this->createDecorator($activity);

        $this->assertSame(55.5, $decorator->getUncorrectedVO2max($config));
    }

    public function testUncorrectedVO2maxFallsBackToPlainVO2maxWhenCorrectionDisabled(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getVO2maxWithElevation')
            ->willReturn(55.5);
        $activity->method('getVO2max')
            ->willReturn(50.0);

        $config = $this->createStub(RunalyzeConfigurationList::class);
        $config->method('useVO2maxCorrectionForElevation')
            ->willReturn(false);

        $decorator = $this->createDecorator($activity);

        $this->assertSame(50.0, $decorator->getUncorrectedVO2max($config));
    }

    public function testUncorrectedVO2maxFallsBackToPlainVO2maxWhenElevationValueIsNotPositive(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getVO2maxWithElevation')
            ->willReturn(0.0);
        $activity->method('getVO2max')
            ->willReturn(50.0);

        $config = $this->createStub(RunalyzeConfigurationList::class);
        $config->method('useVO2maxCorrectionForElevation')
            ->willReturn(true);

        $decorator = $this->createDecorator($activity);

        $this->assertSame(50.0, $decorator->getUncorrectedVO2max($config));
    }

    public function testEffectiveVO2maxMultipliesUncorrectedValueByCorrectionFactor(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getVO2maxWithElevation')
            ->willReturn(0.0);
        $activity->method('getVO2max')
            ->willReturn(50.0);

        $config = $this->createStub(RunalyzeConfigurationList::class);
        $config->method('useVO2maxCorrectionForElevation')
            ->willReturn(false);
        $config->method('getVO2maxCorrectionFactor')
            ->willReturn(1.1);

        $decorator = $this->createDecorator($activity);

        $this->assertEqualsWithDelta(55.0, $decorator->getEffectiveVO2max($config), 0.0001);
    }

    public function testFlightTimeIsNullWhenCadenceIsZero(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getCadence')
            ->willReturn(0);
        $activity->method('getGroundcontact')
            ->willReturn(250);

        $decorator = $this->createDecorator($activity);

        $this->assertNull($decorator->getFlightTime());
        $this->assertNull($decorator->getFlightRatio());
    }

    public function testFlightTimeIsNullWhenGroundContactTimeIsZero(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getCadence')
            ->willReturn(180);
        $activity->method('getGroundcontact')
            ->willReturn(0);

        $decorator = $this->createDecorator($activity);

        $this->assertNull($decorator->getFlightTime());
        $this->assertNull($decorator->getFlightRatio());
    }

    public function testFlightTimeAndRatioAreComputedWhenBothValuesArePositive(): void
    {
        $activity = $this->createStub(Training::class);
        $activity->method('getCadence')
            ->willReturn(180);
        $activity->method('getGroundcontact')
            ->willReturn(120);

        $decorator = $this->createDecorator($activity);

        // 30000 / 180 - 120 = 166.666... - 120 = 46.666...
        $this->assertEqualsWithDelta(46.6667, $decorator->getFlightTime(), 0.001);

        // 1 - (180 * 120) / 30000 = 1 - 0.72 = 0.28
        $this->assertEqualsWithDelta(0.28, $decorator->getFlightRatio(), 0.0001);
    }
}
