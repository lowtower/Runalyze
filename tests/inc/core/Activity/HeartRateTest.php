<?php

declare(strict_types=1);

namespace Runalyze\Activity;

use Runalyze\Athlete;
use Runalyze\Configuration;
use Runalyze\Parameter\Application\HeartRateUnit;

#[\PHPUnit\Framework\Attributes\Group('default')]
class HeartRateTest extends \PHPUnit\Framework\TestCase
{
    public function testWithoutAthlete(): void
    {
        $HR = new HeartRate(150);

        $this->assertFalse($HR->canShowInHRmax());
        $this->assertFalse($HR->canShowInHRrest());
        $this->assertSame(150, $HR->inBPM());
    }

    public function testDynamicSetting(): void
    {
        $HR = new HeartRate(150);
        $this->assertSame(150, $HR->inBPM());

        $HR->setBPM(120);
        $this->assertSame(120, $HR->inBPM());
    }

    public function testWithAthlete(): void
    {
        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertTrue($HR->canShowInHRmax());
        $this->assertTrue($HR->canShowInHRrest());
        $this->assertEquals(80, $HR->inHRmax());
        $this->assertEquals(75, $HR->inHRrest());
    }

    public function testWithOnlyHRmax(): void
    {
        $HR = new HeartRate(150, new Athlete(null, 200));

        $this->assertTrue($HR->canShowInHRmax());
        $this->assertFalse($HR->canShowInHRrest());
        $this->assertEquals(75, $HR->inHRmax());
    }

    public function testConstructingWithNullValueDoesNotThrowByItself(): void
    {
        $HR = new HeartRate(null);

        $this->assertFalse($HR->canShowInHRmax());
        $this->assertFalse($HR->canShowInHRrest());
    }

    public function testInBpmReturnsNullWithoutDeprecationForNullValue(): void
    {
        $HR = new HeartRate(null);

        $deprecations = $this->captureDeprecations(function () use ($HR, &$result): void {
            $result = $HR->inBPM();
        });

        $this->assertSame([], $deprecations);
        $this->assertNull($result);
    }

    public function testSetBpmToNullAfterConstructionReturnsNullFromInBpmWithoutDeprecation(): void
    {
        $HR = new HeartRate(150);
        $HR->setBPM(null);

        $deprecations = $this->captureDeprecations(function () use ($HR, &$result): void {
            $result = $HR->inBPM();
        });

        $this->assertSame([], $deprecations);
        $this->assertNull($result);
    }

    public function testInHRmaxReturnsNullForNullValueWithoutDeprecation(): void
    {
        $HR = new HeartRate(null, new Athlete(null, 200, 40));

        $deprecations = $this->captureDeprecations(function () use ($HR, &$result): void {
            $result = $HR->inHRmax();
        });

        $this->assertSame([], $deprecations);
        $this->assertNull($result);
    }

    public function testInHRrestReturnsNullForNullValueWithoutDeprecation(): void
    {
        $HR = new HeartRate(null, new Athlete(null, 200, 40));

        $deprecations = $this->captureDeprecations(function () use ($HR, &$result): void {
            $result = $HR->inHRrest();
        });

        $this->assertSame([], $deprecations);
        $this->assertNull($result);
    }

    public function testInPercentReturnsNullForNullValueRegardlessOfPreferredUnit(): void
    {
        $HR = new HeartRate(null, new Athlete(null, 200, 40));

        $this->assertNull($HR->inPercent());
    }

    public function testAsBpmFormatsValueWithUnit(): void
    {
        $HR = new HeartRate(160);

        $this->assertSame('160&nbsp;bpm', $HR->asBPM());
    }

    public function testAsHRmaxFormatsPercentageWithUnit(): void
    {
        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame('80&nbsp;%', $HR->asHRmax());
    }

    public function testAsHRrestFormatsPercentageWithUnit(): void
    {
        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame('75&nbsp;%', $HR->asHRrest());
    }

    public function testGetHRmaxReturnsAthletesMaximalHeartRate(): void
    {
        $HR = new HeartRate(150, new Athlete(null, 200, 40));

        $this->assertSame(200, $HR->getHRmax());
    }

    public function testGetHRmaxReturnsNullWithoutAthlete(): void
    {
        $HR = new HeartRate(150);

        $this->assertNull($HR->getHRmax());
    }

    public function testGetHRrestReturnsAthletesRestingHeartRate(): void
    {
        $HR = new HeartRate(150, new Athlete(null, 200, 40));

        $this->assertSame(40, $HR->getHRrest());
    }

    public function testGetHRrestReturnsNullWithoutAthlete(): void
    {
        $HR = new HeartRate(150);

        $this->assertNull($HR->getHRrest());
    }

    public function testGetHRrestReturnsNullWhenAthleteHasOnlyMaximalHeartRate(): void
    {
        $HR = new HeartRate(150, new Athlete(null, 200));

        $this->assertNull($HR->getHRrest());
    }

    /**
     * Regression test for a bug: inHRmax() only checked whether a bpm
     * value was known, not whether an Athlete was known at all. Calling it
     * on a HeartRate without an Athlete used to crash with
     * "Call to a member function maximalHR() on null" instead of returning
     * null, even though the return type is already nullable.
     */
    public function testInHRmaxReturnsNullWithoutAthleteInsteadOfCrashing(): void
    {
        $HR = new HeartRate(160);

        $this->assertNull($HR->inHRmax());
    }

    /**
     * Same regression as above, but for inHRrest().
     */
    public function testInHRrestReturnsNullWithoutAthleteInsteadOfCrashing(): void
    {
        $HR = new HeartRate(160);

        $this->assertNull($HR->inHRrest());
    }

    /**
     * inPercent() delegates to inHRmax() when the preferred unit is not
     * HRreserve (or HRrest cannot be shown), so it inherits the same crash
     * without an Athlete unless the underlying methods guard against it.
     */
    public function testInPercentReturnsNullWithoutAthleteInsteadOfCrashing(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::HRMAX);

        $HR = new HeartRate(160);

        $this->assertNull($HR->inPercent());
    }

    public function testStringUsesBpmWhenPreferredUnitIsBpm(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::BPM);

        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame('160&nbsp;bpm', $HR->string());
    }

    public function testStringUsesHRmaxWhenPreferredUnitIsHRmaxAndAthleteKnown(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::HRMAX);

        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame('80&nbsp;%', $HR->string());
    }

    public function testStringUsesHRreserveWhenPreferredUnitIsHRreserveAndAthleteKnown(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::HRRESERVE);

        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame('75&nbsp;%', $HR->string());
    }

    public function testStringFallsBackToBpmWhenAthleteUnknownEvenIfPreferredUnitIsPercentage(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::HRMAX);

        $HR = new HeartRate(160);

        $this->assertSame('160&nbsp;bpm', $HR->string());
    }

    public function testInPercentReturnsHRmaxValueWhenPreferredUnitIsHRmax(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::HRMAX);

        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame(80.0, $HR->inPercent());
    }

    public function testInPercentReturnsHRrestValueWhenPreferredUnitIsHRreserve(): void
    {
        Configuration::General()->heartRateUnit()->set(HeartRateUnit::HRRESERVE);

        $HR = new HeartRate(160, new Athlete(null, 200, 40));

        $this->assertSame(75.0, $HR->inPercent());
    }

    /**
     * @return string[]
     */
    private function captureDeprecations(callable $callback): array
    {
        $deprecations = [];
        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;

                return true;
            },
            E_DEPRECATED
        );

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
