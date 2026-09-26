<?php

declare(strict_types=1);

namespace Runalyze\Model\Activity;

use PHPUnit\Framework\Attributes\DataProvider;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyObject(): void
    {
        $A = new Entity();

        $this->assertFalse($A->knowsTimezoneOffset());

        $this->assertTrue($A->weather()->isEmpty());
        $this->assertTrue($A->weather()->temperature()->isUnknown());
        $this->assertTrue($A->weather()->condition()->isUnknown());
        $this->assertTrue($A->weather()->humidity()->isUnknown());
        $this->assertTrue($A->weather()->pressure()->isUnknown());
        $this->assertTrue($A->weather()->windDegree()->isUnknown());
        $this->assertTrue($A->weather()->windSpeed()->isUnknown());

        $this->assertTrue($A->splits()->isEmpty());
        $this->assertTrue($A->partner()->isEmpty());
    }

    public function testEmptyWeatherValues(): void
    {
        $A = new Entity([
            Entity::TEMPERATURE => null,
            Entity::WINDDEG => null,
            Entity::WINDSPEED => null,
            Entity::PRESSURE => null,
            Entity::HUMIDITY => null,
        ]);
        $B = new Entity([
            Entity::TEMPERATURE => '',
            Entity::WINDDEG => '',
            Entity::WINDSPEED => '',
            Entity::PRESSURE => '',
            Entity::HUMIDITY => '',
        ]);

        $this->assertTrue($A->weather()->temperature()->isUnknown());
        $this->assertTrue($B->weather()->temperature()->isUnknown());
        $this->assertTrue($A->weather()->windDegree()->isUnknown());
        $this->assertTrue($B->weather()->windDegree()->isUnknown());
        $this->assertTrue($A->weather()->windSpeed()->isUnknown());
        $this->assertTrue($B->weather()->windSpeed()->isUnknown());
        $this->assertTrue($A->weather()->pressure()->isUnknown());
        $this->assertTrue($B->weather()->pressure()->isUnknown());
        $this->assertTrue($A->weather()->humidity()->isUnknown());
        $this->assertTrue($B->weather()->humidity()->isUnknown());
    }

    public function testSynchronizationOfWeather(): void
    {
        $A = new Entity([
            Entity::TEMPERATURE => null,
            Entity::WINDDEG => null,
            Entity::WINDSPEED => null,
            Entity::PRESSURE => null,
            Entity::HUMIDITY => null,
        ]);
        $A->weather()
            ->temperature()
            ->setTemperature(17);
        $A->weather()
            ->windSpeed()
            ->set(10);
        $A->weather()
            ->windDegree()
            ->set(180);
        $A->weather()
            ->humidity()
            ->set(65);
        $A->weather()
            ->pressure()
            ->set(1025);
        $A->synchronize();

        $this->assertEquals(17, $A->get(Entity::TEMPERATURE));
        $this->assertEquals(10, $A->get(Entity::WINDSPEED));
        $this->assertEquals(180, $A->get(Entity::WINDDEG));
        $this->assertEquals(65, $A->get(Entity::HUMIDITY));
        $this->assertEquals(1025, $A->get(Entity::PRESSURE));
    }

    public function testIsNight(): void
    {
        $EmptyEntity = new Entity([]);
        $this->assertEquals(false, $EmptyEntity->knowsIfItIsNight());
        $this->assertEquals(false, $EmptyEntity->isNight());

        $IsNotNight = new Entity([
            Entity::IS_NIGHT => false,
        ]);
        $this->assertEquals(true, $IsNotNight->knowsIfItIsNight());
        $this->assertEquals(false, $IsNotNight->isNight());

        $IsNight = new Entity([
            Entity::IS_NIGHT => true,
        ]);
        $this->assertEquals(true, $IsNight->knowsIfItIsNight());
        $this->assertEquals(true, $IsNight->isNight());

    }

    #[DataProvider('provideNullableNumericGetters')]
    public function testNullableGetterReturnsNullWhenUnderlyingValueIsNull(string $method, string $key, string $nonNullValue, int|float $expected): void
    {
        $Entity = new Entity([
            $key => null,
        ]);

        $this->assertNull($Entity->{$method}());
    }

    #[DataProvider('provideNullableNumericGetters')]
    public function testNullableGetterCastsNonNullValueCorrectly(string $method, string $key, string $nonNullValue, int|float $expected): void
    {
        $Entity = new Entity([
            $key => $nonNullValue,
        ]);

        $this->assertSame($expected, $Entity->{$method}());
    }

    /**
     * @return array<string, string[]|int[]|float[]>
     */
    public static function provideNullableNumericGetters(): array
    {
        return [
            // int-returning (39)
            'energy' => ['energy', Entity::ENERGY, '250', 250],
            'energyInRest' => ['energyInRest', Entity::ENERGY_REST, '50', 50],
            'hrAvg' => ['hrAvg', Entity::HR_AVG, '140', 140],
            'hrMax' => ['hrMax', Entity::HR_MAX, '180', 180],
            'hrAvgActive' => ['hrAvgActive', Entity::HR_AVG_ACTIVE, '145', 145],
            'maxHrUser' => ['maxHrUser', Entity::MAX_HR_USER, '190', 190],
            'fitRecoveryTime' => ['fitRecoveryTime', Entity::FIT_RECOVERY_TIME, '30', 30],
            'fitHRVscore' => ['fitHRVscore', Entity::FIT_HRV_ANALYSIS, '5', 5],
            'fitTrainingEffectBenefit' => ['fitTrainingEffectBenefit', Entity::FIT_TRAINING_EFFECT_BENEFIT, '3', 3],
            'fitPerformanceCondition' => ['fitPerformanceCondition', Entity::FIT_PERFORMANCE_CONDITION, '2', 2],
            'fitPerformanceConditionEnd' => ['fitPerformanceConditionEnd', Entity::FIT_PERFORMANCE_CONDITION_END, '4', 4],
            'fitLactateThresholdHR' => ['fitLactateThresholdHR', Entity::FIT_LACTATE_THRESHOLD_HR, '165', 165],
            'fitTotalAscent' => ['fitTotalAscent', Entity::FIT_TOTAL_ASCENT, '500', 500],
            'fitTotalDescent' => ['fitTotalDescent', Entity::FIT_TOTAL_DESCENT, '480', 480],
            'fitSelfEvaluationFeeling' => ['fitSelfEvaluationFeeling', Entity::FIT_SELF_ELEVATION_FEELING, '2', 2],
            'fitSelfEvaluationPerceivedEffort' => ['fitSelfEvaluationPerceivedEffort', Entity::FIT_SELF_ELEVATION_PERCEIVED_EFFORT, '5', 5],
            'fitLoadPeak' => ['fitLoadPeak', Entity::FIT_LOAD_PEAK, '100', 100],
            'fitRunTime' => ['fitRunTime', Entity::FIT_RUN_TIME, '3000', 3000],
            'fitWalkTime' => ['fitWalkTime', Entity::FIT_WALK_TIME, '200', 200],
            'fitStandTime' => ['fitStandTime', Entity::FIT_STAND_TIME, '60', 60],
            'fitSweatLoss' => ['fitSweatLoss', Entity::FIT_SWEAT_LOSS, '750', 750],
            'avgRespirationRate' => ['avgRespirationRate', Entity::AVG_RESPIRATION_RATE, '18', 18],
            'maxRespirationRate' => ['maxRespirationRate', Entity::MAX_RESPIRATION_RATE, '35', 35],
            'rpe' => ['rpe', Entity::RPE, '7', 7],
            'trimp' => ['trimp', Entity::TRIMP, '120', 120],
            'cadence' => ['cadence', Entity::CADENCE, '180', 180],
            'power' => ['power', Entity::POWER, '250', 250],
            'strideLength' => ['strideLength', Entity::STRIDE_LENGTH, '120', 120],
            'totalStrokes' => ['totalStrokes', Entity::TOTAL_STROKES, '40', 40],
            'swolf' => ['swolf', Entity::SWOLF, '35', 35],
            'totalCycles' => ['totalCycles', Entity::TOTAL_CYCLES, '20', 20],
            'groundcontact' => ['groundcontact', Entity::GROUNDCONTACT, '250', 250],
            'verticalOscillation' => ['verticalOscillation', Entity::VERTICAL_OSCILLATION, '90', 90],
            'groundContactBalance' => ['groundContactBalance', Entity::GROUNDCONTACT_BALANCE, '5000', 5000],
            'groundContactBalanceLeft' => ['groundContactBalanceLeft', Entity::GROUNDCONTACT_BALANCE, '5000', 5000],
            'verticalRatio' => ['verticalRatio', Entity::VERTICAL_RATIO, '75', 75],
            'footstrikeTypeLeft' => ['footstrikeTypeLeft', Entity::AVG_FOOTSTRIKE_TYPE_LEFT, '2', 2],
            'footstrikeTypeRight' => ['footstrikeTypeRight', Entity::AVG_FOOTSTRIKE_TYPE_RIGHT, '3', 3],
            'typeid' => ['typeid', Entity::TYPEID, '7', 7],
            // float-returning (13)
            'vo2maxByHeartRate' => ['vo2maxByHeartRate', Entity::VO2MAX, '45.5', 45.5],
            'vo2maxByTime' => ['vo2maxByTime', Entity::VO2MAX_BY_TIME, '46.2', 46.2],
            'vo2maxWithElevation' => ['vo2maxWithElevation', Entity::VO2MAX_WITH_ELEVATION, '47.0', 47.0],
            'fitVO2maxEstimate' => ['fitVO2maxEstimate', Entity::FIT_VO2MAX_ESTIMATE, '48.3', 48.3],
            'fitTrainingEffect' => ['fitTrainingEffect', Entity::FIT_TRAINING_EFFECT, '3.5', 3.5],
            'fitAnaerobicTrainingEffect' => ['fitAnaerobicTrainingEffect', Entity::FIT_ANAEROBIC_TRAINING_EFFECT, '2.1', 2.1],
            'fitLactateThresholdPace' => ['fitLactateThresholdPace', Entity::FIT_LACTATE_THRESHOLD_PACE, '3.33', 3.33],
            'impactGsLeft' => ['impactGsLeft', Entity::AVG_IMPACT_GS_LEFT, '1.2', 1.2],
            'impactGsRight' => ['impactGsRight', Entity::AVG_IMPACT_GS_RIGHT, '1.3', 1.3],
            'brakingGsLeft' => ['brakingGsLeft', Entity::AVG_BRAKING_GS_LEFT, '0.8', 0.8],
            'brakingGsRight' => ['brakingGsRight', Entity::AVG_BRAKING_GS_RIGHT, '0.9', 0.9],
            'pronationExcursionLeft' => ['pronationExcursionLeft', Entity::AVG_PRONATION_EXCURSION_LEFT, '5.5', 5.5],
            'pronationExcursionRight' => ['pronationExcursionRight', Entity::AVG_PRONATION_EXCURSION_RIGHT, '6.1', 6.1],
        ];
    }

    public function testGroundContactBalanceRightReturnsNullWhenUnderlyingValueIsNull(): void
    {
        $Entity = new Entity([
            Entity::GROUNDCONTACT_BALANCE => null,
        ]);

        $this->assertNull($Entity->groundContactBalanceRight());
    }

    public function testGroundContactBalanceRightComputesComplementOfBalance(): void
    {
        $Entity = new Entity([
            Entity::GROUNDCONTACT_BALANCE => '5000',
        ]);

        $this->assertSame(5000, $Entity->groundContactBalanceRight());
    }

    public function testPaceGoalReturnsNullWhenUnderlyingValueIsNull(): void
    {
        $Entity = new Entity([
            Entity::PACE_GOAL => null,
        ]);

        $this->assertNull($Entity->paceGoal());
    }

    public function testPaceGoalDecodesJsonWhenValueIsPresent(): void
    {
        $Entity = new Entity([
            Entity::PACE_GOAL => '{"foo":"bar"}',
        ]);

        $this->assertSame([
            'foo' => 'bar',
        ], $Entity->paceGoal());
    }

    public function testFitSweatLossReturnsCastValue(): void
    {
        $Entity = new Entity([
            Entity::FIT_SWEAT_LOSS => '750',
        ]);

        $this->assertSame(750, $Entity->fitSweatLoss());
    }

    public function testFitSweatLossReturnsNullWhenUnderlyingValueIsNull(): void
    {
        $Entity = new Entity([
            Entity::FIT_SWEAT_LOSS => null,
        ]);

        $this->assertNull($Entity->fitSweatLoss());
    }
}
