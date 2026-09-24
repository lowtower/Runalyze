<?php

namespace Runalyze\Calculation;

use DB;
use Runalyze\Configuration;
use Runalyze\Util\LocalTime;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
class BasicEnduranceTest extends \PHPUnit\Framework\TestCase
{
    protected BasicEndurance $object;

    protected function setUp(): void
    {
        $this->object = new BasicEndurance();
        DB::getInstance()->exec('DELETE FROM `runalyze_training`');
    }

    protected function tearDown(): void
    {
        DB::getInstance()->exec('DELETE FROM `runalyze_training`');
    }

    public function testSetterAndGetter(): void
    {
        $this->object->setEffectiveVO2max(50);
        $this->object->setDaysToRecognizeForLongjogs(30);
        $this->object->setDaysToRecognizeForWeekKilometer(60);
        $this->object->setMinimalDaysToRecognizeForWeekKilometer(30);
        $this->object->setMinimalDistanceForLongjogs(10);

        $this->assertEquals(50, $this->object->getUsedEffectiveVO2max());
        $this->assertEquals(30, $this->object->getDaysToRecognizeForLongjogs());
        $this->assertEquals(60, $this->object->getDaysToRecognizeForWeekKilometer());
        $this->assertEquals(30, $this->object->getMinimalDaysToRecognizeForWeekKilometer());
        $this->assertEquals(10, $this->object->getMinimalDistanceForLongjogs());
    }

    public function testSetterAndGetterForPercentages(): void
    {
        $this->object->setPercentageForLongjogs(0.4);
        $this->assertEquals(0.4, $this->object->getPercentageForLongjogs());
        $this->assertEquals(0.6, $this->object->getPercentageForWeekKilometer());

        $this->object->setPercentageForWeekKilometer(0.1);
        $this->assertEquals(0.9, $this->object->getPercentageForLongjogs());
        $this->assertEquals(0.1, $this->object->getPercentageForWeekKilometer());

        $this->object->setPercentageForLongjogs(15);
        $this->assertEquals(1, $this->object->getPercentageForLongjogs());
        $this->assertEquals(0, $this->object->getPercentageForWeekKilometer());

        $this->object->setPercentageForLongjogs(-15);
        $this->assertEquals(1, $this->object->getPercentageForLongjogs());
        $this->assertEquals(0, $this->object->getPercentageForWeekKilometer());
    }

    public function testThatMinimalVO2maxIsUsed(): void
    {
        $this->object->setEffectiveVO2max(20);
        $this->assertEquals(25, $this->object->getUsedEffectiveVO2max());
    }

    public function testTargets(): void
    {
        $this->object->setMinimalDistanceForLongjogs(10);

        $this->object->setEffectiveVO2max(30);
        $this->assertEqualsWithDelta(47.5, $this->object->getTargetWeekKm(), 1);
        $this->assertEqualsWithDelta(24, $this->object->getRealTargetLongjogKmPerWeek(), 1);

        $this->object->setEffectiveVO2max(40);
        $this->assertEqualsWithDelta(66, $this->object->getTargetWeekKm(), 1);
        $this->assertEqualsWithDelta(27.5, $this->object->getRealTargetLongjogKmPerWeek(), 1);

        $this->object->setEffectiveVO2max(50);
        $this->assertEqualsWithDelta(85, $this->object->getTargetWeekKm(), 1);
        $this->assertEqualsWithDelta(30, $this->object->getRealTargetLongjogKmPerWeek(), 1);

        $this->object->setEffectiveVO2max(60);
        $this->assertEqualsWithDelta(104, $this->object->getTargetWeekKm(), 1);
        $this->assertEqualsWithDelta(32.5, $this->object->getRealTargetLongjogKmPerWeek(), 1);

        $this->object->setEffectiveVO2max(70);
        $this->assertEqualsWithDelta(124, $this->object->getTargetWeekKm(), 1);
        $this->assertEqualsWithDelta(34.5, $this->object->getRealTargetLongjogKmPerWeek(), 1);

        $this->object->setEffectiveVO2max(80);
        $this->assertEqualsWithDelta(145, $this->object->getTargetWeekKm(), 1);
        $this->assertEqualsWithDelta(36, $this->object->getRealTargetLongjogKmPerWeek(), 1);
    }

    public function testForVO2max30(): void
    {
        $this->object->setEffectiveVO2max(30);
        $this->object->setDaysToRecognizeForLongjogs(7);
        $this->object->setDaysToRecognizeForWeekKilometer(7);
        $this->object->setMinimalDaysToRecognizeForWeekKilometer(7);
        $this->object->setMinimalDistanceForLongjogs(10);
        $this->object->setPercentageForLongjogs(0.33);
        $this->object->setPercentageForWeekKilometer(0.67);

        DB::getInstance()->insert('training', ['sportid', 'time', 'distance', 's'], [Configuration::General()->runningSport(), LocalTime::fromServerTime(time())->getTimestamp() - DAY_IN_S, 15, 2]);
        DB::getInstance()->insert('training', ['sportid', 'time', 'distance', 's'], [Configuration::General()->runningSport(), LocalTime::fromServerTime(time())->getTimestamp() - 3 * DAY_IN_S, 15, 2]);
        DB::getInstance()->insert('training', ['sportid', 'time', 'distance', 's'], [Configuration::General()->runningSport(), LocalTime::fromServerTime(time())->getTimestamp() - 8 * DAY_IN_S, 42, 2]);

        $Results = $this->object->asArray();
        $this->assertEquals(30, $Results['weekkm-result']);
        $this->assertEqualsWithDelta(0.36, $Results['longjog-result'], 0.01);
        $this->assertEqualsWithDelta(0.63, $Results['weekkm-percentage'], 0.01);
        $this->assertEqualsWithDelta(0.36, $Results['longjog-percentage'], 0.01);
        $this->assertEqualsWithDelta(54, $Results['percentage'], 0.01);
        $this->assertEquals(54, $this->object->value());
        $this->assertEquals('54 %', $this->object->valueInPercent());

        $this->object->setPercentageForLongjogs(1);
        $this->assertEquals(36, $this->object->value());

        $this->object->setDaysToRecognizeForLongjogs(14);
        $this->assertEquals(240, $this->object->value());

        // Attention: depens on START_TIME
        $this->object->setMinimalDaysToRecognizeForWeekKilometer(70);
        $this->object->setDaysToRecognizeForWeekKilometer(70);
        $this->object->setPercentageForLongjogs(0.01);
        $this->assertEquals(17, $this->object->value());
    }

    public function testValueInPercentIsLimitedToHundredPercent(): void
    {
        $this->object->setEffectiveVO2max(30);
        $this->object->setDaysToRecognizeForLongjogs(14);
        $this->object->setDaysToRecognizeForWeekKilometer(7);
        $this->object->setMinimalDaysToRecognizeForWeekKilometer(7);
        $this->object->setMinimalDistanceForLongjogs(10);

        DB::getInstance()->insert(
            'training',
            ['sportid', 'time', 'distance', 's'],
            [
                Configuration::General()->runningSport(),
                time() - DAY_IN_S,
                50,
                2,
            ]
        );

        $this->assertGreaterThan(100, $this->object->value());
        $this->assertSame('100 %', $this->object->valueInPercent());
    }

    public function testValueInPercentIsLimitedToZeroPercent(): void
    {
        $this->object->setEffectiveVO2max(80);

        $this->assertSame('0 %', $this->object->valueInPercent());
    }

    public function testDataForIgnoresShortActivitiesForLongJogPoints(): void
    {
        $this->object->setMinimalDistanceForLongjogs(10);

        $timestamp = time();

        $result = $this->object->dataFor([
            [
                'time' => $timestamp - DAY_IN_S,
                'distance' => 8,
            ],
        ], $timestamp);

        $this->assertEquals(8, $result['km']);
        $this->assertEquals(0, $result['sum']);
    }

    public function testDistanceExactlyAtLongJogLimitDoesNotCount(): void
    {
        $this->object->setMinimalDistanceForLongjogs(10);

        $timestamp = time();

        $result = $this->object->dataFor([
            [
                'time' => $timestamp - DAY_IN_S,
                'distance' => 10,
            ],
        ], $timestamp);

        $this->assertEquals(10, $result['km']);
        $this->assertEquals(0, $result['sum']);
    }

    public function testDataForIgnoresActivitiesOutsideWeekKmRange(): void
    {
        $this->object->setDaysToRecognizeForWeekKilometer(7);
        $this->object->setMinimalDaysToRecognizeForWeekKilometer(7);

        $timestamp = time();

        $result = $this->object->dataFor([
            [
                'time' => $timestamp - 8 * DAY_IN_S,
                'distance' => 10,
            ],
        ], $timestamp);

        $this->assertEquals(0, $result['km']);
        $this->assertEquals(0, $result['sum']);
    }

    public function testDataForCountsLongJogPoints(): void
    {
        $this->object->setEffectiveVO2max(30);
        $this->object->setMinimalDistanceForLongjogs(10);

        $timestamp = time();

        $result = $this->object->dataFor([
            [
                'time' => $timestamp - DAY_IN_S,
                'distance' => 20,
            ],
        ], $timestamp);

        $this->assertEquals(20, $result['km']);
        $this->assertGreaterThan(0, $result['sum']);
    }

    /**
     * Regression test for a bug: dataFor() used to sum week-km using the
     * raw $this->DAYS_FOR_WEEK_KM property directly, while asArray() always
     * divides by the clamped getDaysForWeekKm() (which is bounded between
     * DAYS_FOR_WEEK_KM_MIN and DAYS_FOR_WEEK_KM based on account age via
     * Time::diffInDays(START_TIME)). This mismatch between the summation
     * window and the divisor could distort the resulting percentage for
     * accounts whose age causes the getter to clamp.
     *
     * The minimum is deliberately set to an unrealistically large value
     * (100000 days, ~273 years) so that no real account age can exceed it.
     * This forces getDaysForWeekKm() to clamp to that minimum regardless
     * of the actual value of START_TIME in this test environment, making
     * the test deterministic without needing to control START_TIME itself.
     *
     * The raw (unclamped) property is set even higher (200000 days), which
     * is exactly the value the pre-fix dataFor() used directly. An activity
     * placed between the two (150000 days old) must be excluded by the
     * fixed, clamped window, whereas the old, buggy implementation would
     * have included it.
     */
    public function testDataForUsesClampedWeekKmWindowConsistentWithAsArray(): void
    {
        $this->object->setDaysToRecognizeForWeekKilometer(200000);
        $this->object->setMinimalDaysToRecognizeForWeekKilometer(100000);

        $timestamp = time();

        $result = $this->object->dataFor([
            [
                'time' => $timestamp - 150000 * DAY_IN_S,
                'distance' => 10,
            ],
        ], $timestamp);

        $this->assertEquals(0, $result['km']);
    }

    public function testAsArrayUsesProvidedData(): void
    {
        $this->object->setEffectiveVO2max(30);

        $result = $this->object->asArray(
            time(),
            [
                'km' => 50,
                'sum' => 2.0,
            ]
        );

        $this->assertEquals(50, $result['weekkm-result']);
        $this->assertEquals(2.0, $result['longjog-result']);
    }

    public function testGetQueryForLongJogsContainsPointsCalculation(): void
    {
        $query = $this->object->getQuery(time(), true);

        $this->assertStringContainsString(
            'as `points`',
            $query
        );
    }

    /**
     * Complements testGetQueryForLongJogsContainsPointsCalculation(),
     * which only covers the $onlyLongjogs = true branch.
     */
    public function testGetQueryForWeekKmContainsKmAndSumCalculation(): void
    {
        $query = $this->object->getQuery(time(), false);

        $this->assertStringContainsString('as `km`', $query);
        $this->assertStringContainsString('as `sum`', $query);
    }

    public function testValueReturnsCalculatedPercentage(): void
    {
        $this->object->setEffectiveVO2max(30);

        $result = $this->object->asArray(
            time(),
            [
                'km' => 0,
                'sum' => 0,
            ]
        );

        $this->assertEquals(
            $result['percentage'],
            $this->object->value()
        );
    }

    public function testGetConstReturnsCachedValue(): void
    {
        $reflection = new \ReflectionProperty(
            BasicEndurance::class,
            'CONST_VALUE'
        );
        $reflection->setValue(null, 123);

        $this->assertSame(
            123,
            BasicEndurance::getConst()
        );
    }

    public function testGetConstUsesConfigurationValue(): void
    {
        Configuration::Data()->updateBasicEndurance(77);

        $reflection = new \ReflectionProperty(
            BasicEndurance::class,
            'CONST_VALUE'
        );
        $reflection->setValue(null, false);

        $this->assertSame(
            77,
            BasicEndurance::getConst()
        );
    }

    public function testGetConstTriggersRecalculation(): void
    {
        Configuration::Data()->updateBasicEndurance(0);

        $reflection = new \ReflectionProperty(
            BasicEndurance::class,
            'CONST_VALUE'
        );
        $reflection->setValue(null, false);

        $value = BasicEndurance::getConst();

        $this->assertNotFalse($value);
    }

    public function testRecalculateValueUpdatesConstValue(): void
    {
        $reflection = new \ReflectionProperty(
            BasicEndurance::class,
            'CONST_VALUE'
        );
        $reflection->setValue(null, false);

        BasicEndurance::recalculateValue();

        $this->assertNotFalse(
            $reflection->getValue()
        );
    }
}
