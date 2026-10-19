<?php

declare(strict_types=1);

namespace Runalyze\View\Activity;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Model;

#[Group('default')]
final class ContextTest extends TestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = new \ReflectionClass(Context::class)
            ->newInstanceWithoutConstructor();
    }

    public function testActivityReturnsThePropertySetViaConstructor(): void
    {
        $activity = $this->newInstanceOf(Model\Activity\Entity::class);
        $this->setProperty('Activity', $activity);

        $this->assertSame($activity, $this->context->activity());
    }

    public function testTrackdataReturnsThePropertySetViaConstructor(): void
    {
        $trackdata = $this->newInstanceOf(Model\Trackdata\Entity::class);
        $this->setProperty('Trackdata', $trackdata);

        $this->assertSame($trackdata, $this->context->trackdata());
    }

    public function testSwimdataReturnsThePropertySetViaConstructor(): void
    {
        $swimdata = $this->newInstanceOf(Model\Swimdata\Entity::class);
        $this->setProperty('Swimdata', $swimdata);

        $this->assertSame($swimdata, $this->context->swimdata());
    }

    public function testHrvReturnsThePropertySetViaConstructor(): void
    {
        $hrv = $this->newInstanceOf(Model\HRV\Entity::class);
        $this->setProperty('HRV', $hrv);

        $this->assertSame($hrv, $this->context->hrv());
    }

    public function testSportReturnsThePropertySetViaConstructor(): void
    {
        $sport = $this->newInstanceOf(Model\Sport\Entity::class);
        $this->setProperty('Sport', $sport);

        $this->assertSame($sport, $this->context->sport());
    }

    public function testRouteReturnsThePropertySetViaConstructor(): void
    {
        $route = $this->newInstanceOf(Model\Route\Entity::class);
        $this->setProperty('Route', $route);

        $this->assertSame($route, $this->context->route());
    }

    public function testRaceResultReturnsThePropertySetViaConstructor(): void
    {
        $raceResult = $this->newInstanceOf(Model\RaceResult\Entity::class);
        $this->setProperty('RaceResult', $raceResult);

        $this->assertSame($raceResult, $this->context->raceResult());
    }

    public function testDataviewReturnsThePropertySetViaConstructor(): void
    {
        $dataview = $this->newInstanceOf(Dataview::class);
        $this->setProperty('Dataview', $dataview);

        $this->assertSame($dataview, $this->context->dataview());
    }

    public function testHasRouteIsTrueWhenRoutePropertyIsARouteEntity(): void
    {
        $this->setProperty('Route', $this->newInstanceOf(Model\Route\Entity::class));

        $this->assertTrue($this->context->hasRoute());
    }

    public function testHasRouteIsFalseWhenRoutePropertyIsNull(): void
    {
        $this->setProperty('Route', null);

        $this->assertFalse($this->context->hasRoute());
    }

    public function testHasSwimdataIsTrueWhenSwimdataPropertyIsASwimdataEntity(): void
    {
        $this->setProperty('Swimdata', $this->newInstanceOf(Model\Swimdata\Entity::class));

        $this->assertTrue($this->context->hasSwimdata());
    }

    public function testHasSwimdataIsFalseWhenSwimdataPropertyIsNull(): void
    {
        $this->setProperty('Swimdata', null);

        $this->assertFalse($this->context->hasSwimdata());
    }

    private function newInstanceOf(string $class): object
    {
        return new \ReflectionClass($class)->newInstanceWithoutConstructor();
    }

    private function setProperty(string $name, mixed $value): void
    {
        $property = new \ReflectionProperty(Context::class, $name);
        $property->setValue($this->context, $value);
    }
}
