<?php

declare(strict_types=1);

namespace Runalyze\View\Leaflet;

use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Configuration;
use Runalyze\Model\Route\Entity as RouteEntity;
use Runalyze\Model\Trackdata\Entity;
use Runalyze\Parameter\Application\DistanceUnitSystem;

final class ActivityDistanceMarkerTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('distanceUnits')]
    public function testDistanceMarkersDisplaySplitAndCumulativeTimes(string $system, float $step, string $unit): void
    {
        $route = new RouteEntity([
            RouteEntity::GEOHASHES => ['u0v90mey4wr9', 'u0v90rp9092c', 'u0v90xh7kdtg'],
        ]);
        $trackdata = new Entity([
            Entity::TIME => [0, 300, 300],
            Entity::DISTANCE => [0, $step, 2 * $step],
        ]);
        $distanceUnitSystem = Configuration::General()->distanceUnitSystem();
        $precision = Configuration::ActivityView()->routePrecision();
        $previousSystem = $distanceUnitSystem->valueAsString();
        $previousPrecision = $precision->valueAsString();

        try {
            $distanceUnitSystem->set($system);
            $precision->set('1');
            $js = new Activity('test', $route, $trackdata)->js();
        } finally {
            $distanceUnitSystem->set($previousSystem);
            $precision->set($previousPrecision);
        }

        $this->assertStringContainsString((string) json_encode('<strong>1. ' . $unit . '</strong> in 5:00<br><strong>Time:</strong> 5:00'), $js);
        $this->assertStringContainsString((string) json_encode('<strong>2. ' . $unit . '</strong> in -:--<br><strong>Time:</strong> 5:00'), $js);
    }

    public static function distanceUnits(): iterable
    {
        yield 'metric' => [DistanceUnitSystem::METRIC, 1.0, 'km'];
        yield 'imperial' => [DistanceUnitSystem::IMPERIAL, 1.61, 'mi'];
    }
}
