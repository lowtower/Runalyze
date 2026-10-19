<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Activity;

use Runalyze\Model;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldFactory')]
final class CalculatorTest extends \PHPUnit\Framework\TestCase
{
    public function testGeneralFunctionality(): void
    {
        $Calculator = new Calculator(new Model\Activity\Entity([
            Model\Activity\Entity::DISTANCE => 10,
            Model\Activity\Entity::TIME_IN_SECONDS => 3000,
            Model\Activity\Entity::HR_AVG => 150,
        ]));

        $this->assertGreaterThan(0, $Calculator->estimateVO2maxByTime());
        $this->assertGreaterThan(0, $Calculator->estimateVO2maxByHeartRate());
        $this->assertGreaterThan(0, $Calculator->estimateVO2maxByHeartRateWithElevation());
        $this->assertGreaterThan(0, $Calculator->calculateTrimp());

        $this->assertGreaterThan($Calculator->estimateVO2maxByTime(), $Calculator->estimateVO2maxByHeartRate());
    }

    public function testEmptyValues(): void
    {
        $Calculator = new Calculator(new Model\Activity\Entity());

        $this->assertEquals(0, $Calculator->estimateVO2maxByTime());
        $this->assertEquals(0, $Calculator->estimateVO2maxByHeartRate());
        $this->assertEquals(0, $Calculator->estimateVO2maxByHeartRateWithElevation());
        $this->assertEquals(0, $Calculator->calculateTrimp());
    }

    public function testCalculationsWithElevation(): void
    {
        $Activity = new Model\Activity\Entity([
            Model\Activity\Entity::DISTANCE => 10,
            Model\Activity\Entity::TIME_IN_SECONDS => 3000,
            Model\Activity\Entity::HR_AVG => 150,
            Model\Activity\Entity::ELEVATION => 100,
        ]);

        $CalculatorOnlyActivity = new Calculator($Activity);
        $CalculatorOnlyElevation = new Calculator($Activity, null, new Model\Route\Entity([
            Model\Route\Entity::ELEVATION => 500,
        ]));
        $CalculatorUpAndDown = new Calculator($Activity, null, new Model\Route\Entity([
            Model\Route\Entity::ELEVATION_UP => 500,
            Model\Route\Entity::ELEVATION_DOWN => 100,
        ]));
        $CalculatorOnlyDown = new Calculator($Activity, null, new Model\Route\Entity([
            Model\Route\Entity::ELEVATION => 500,
            Model\Route\Entity::ELEVATION_UP => 0,
            Model\Route\Entity::ELEVATION_DOWN => 500,
        ]));

        $this->assertGreaterThan(
            $CalculatorOnlyElevation->estimateVO2maxByHeartRateWithElevation(),
            $CalculatorUpAndDown->estimateVO2maxByHeartRateWithElevation()
        );
        $this->assertGreaterThan(
            $CalculatorOnlyDown->estimateVO2maxByHeartRateWithElevation(),
            $CalculatorOnlyElevation->estimateVO2maxByHeartRateWithElevation()
        );
        $this->assertGreaterThan(
            $CalculatorOnlyActivity->estimateVO2maxByHeartRateWithElevation(),
            $CalculatorOnlyElevation->estimateVO2maxByHeartRateWithElevation()
        );
        $this->assertGreaterThan(
            $CalculatorOnlyActivity->estimateVO2maxByHeartRate(),
            $CalculatorOnlyActivity->estimateVO2maxByHeartRateWithElevation()
        );

        $this->assertSame(
            $CalculatorUpAndDown->estimateVO2maxByHeartRateWithElevationFor(500, 100),
            $CalculatorUpAndDown->estimateVO2maxByHeartRateWithElevation()
        );
        $this->assertSame(
            $CalculatorOnlyElevation->estimateVO2maxByHeartRateWithElevationFor(500, 500),
            $CalculatorOnlyElevation->estimateVO2maxByHeartRateWithElevation()
        );
        $this->assertSame(
            $CalculatorOnlyActivity->estimateVO2maxByHeartRateWithElevationFor(100, 100),
            $CalculatorOnlyActivity->estimateVO2maxByHeartRateWithElevation()
        );
    }

    public function testCalculationsWithTrackdata(): void
    {
        $Activity = new Model\Activity\Entity([
            Model\Activity\Entity::DISTANCE => 10,
            Model\Activity\Entity::TIME_IN_SECONDS => 3000,
            Model\Activity\Entity::HR_AVG => 150,
        ]);

        $CalculatorOnlyActivity = new Calculator($Activity);
        $CalculatorWithTrackdata = new Calculator($Activity, new Model\Trackdata\Entity([
            Model\Trackdata\Entity::TIME => [1500, 3000],
            Model\Trackdata\Entity::HEARTRATE => [125, 175],
        ]));

        $this->assertGreaterThan($CalculatorOnlyActivity->calculateTrimp(), $CalculatorWithTrackdata->calculateTrimp());
    }

    public function testCalculationWithEmptyHeartrateArray(): void
    {
        $Activity = new Model\Activity\Entity([
            Model\Activity\Entity::DISTANCE => 10,
            Model\Activity\Entity::TIME_IN_SECONDS => 3000,
            Model\Activity\Entity::HR_AVG => 150,
        ]);

        $CalculatorOnlyActivity = new Calculator($Activity);
        $CalculatorWithTrackdata = new Calculator($Activity, new Model\Trackdata\Entity([
            Model\Trackdata\Entity::TIME => [1500, 3000],
            Model\Trackdata\Entity::HEARTRATE => [0, 0],
        ]));

        $this->assertSame($CalculatorOnlyActivity->calculateTrimp(), $CalculatorWithTrackdata->calculateTrimp());
    }
}
