<?php

declare(strict_types=1);

namespace Runalyze\Data\Weather;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Activity\Temperature as ActivityTemperature;
use Runalyze\Model\Activity\Entity;
use Runalyze\Parameter\Application\TemperatureUnit;
use Runalyze\View\Activity\Dataview;

final class WindChillFactorTest extends \PHPUnit\Framework\TestCase
{
    public function testUnknownInputData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WindChillFactor(
            new WindSpeed(20),
            new ActivityTemperature()
        );
    }

    public function testSimpleExample(): void
    {
        $WindChill = new WindChillFactor(
            new WindSpeed(20),
            new ActivityTemperature(10),
            3.6
        );

        $this->assertEqualsWithDelta(7.1, $WindChill->value(), 0.1);
    }

    #[DataProvider('activitySpeeds')]
    public function testActivityWindChillUsesTheRoundedSpeed(?float $distance, float $windSpeed, float $expectedTemperature): void
    {
        $activity = new Entity([
            Entity::TIME_IN_SECONDS => 1014,
            Entity::DISTANCE => $distance,
            Entity::TEMPERATURE => -5.0,
            Entity::WINDSPEED => $windSpeed,
        ]);

        $this->assertSame($expectedTemperature, new Dataview($activity)->windChillFactor()->value());
    }

    public static function activitySpeeds(): iterable
    {
        yield 'rounded speed reaches the threshold' => [1.0, 1.4, -7.26];
        yield 'combined speed remains below the threshold' => [1.0, 1.3, -5.0];
        yield 'unknown distance contributes zero speed' => [null, 1.4, -5.0];
    }

    public function testThatObjectIsCloned(): void
    {
        $Temperature = new ActivityTemperature(37.16, new TemperatureUnit(TemperatureUnit::FAHRENHEIT));
        $WindChill = new WindChillFactor(
            new WindSpeed(0),
            $Temperature
        );

        $this->assertEquals($Temperature->valueInPreferredUnit(), $WindChill->adjustedTemperature()->valueInPreferredUnit());
        $this->assertSame($Temperature->string(true, 2), $WindChill->string(true, 2));
    }
}
