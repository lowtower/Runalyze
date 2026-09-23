<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\VO2maxAnalysis;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis\RaceAnalysis;
use Runalyze\Model\Activity\Entity as ActivityEntity;

class RaceAnalysisTest extends TestCase
{
    private function createActivityMock(): ActivityEntity&\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(ActivityEntity::class);
    }

    public function testGetNameDelegatesToActivityTitle(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('title')
            ->willReturn('Sunday Long Run');

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame('Sunday Long Run', $race->getName());
    }

    public function testGetVO2maxByTimeDelegatesToActivity(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByTime')
            ->willReturn(55.5);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame(55.5, $race->getVO2maxByTime());
    }

    public function testGetHeartRateInBpmDelegatesToActivity(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('hrAvg')
            ->willReturn(165);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame(165, $race->getHeartRateInBpm());
    }

    public function testGetVO2maxByHeartRateDelegatesToActivity(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByHeartRate')
            ->willReturn(48.2);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame(48.2, $race->getVO2maxByHeartRate());
    }

    public function testGetVO2maxByShapeReturnsConstructorValue(): void
    {
        $race = new RaceAnalysis($this->createActivityMock(), 1.0, 52.3);

        $this->assertSame(52.3, $race->getVO2maxByShape());
    }

    public function testGetVO2maxByHeartRateAfterCorrectionMultipliesFactorWithActivityValue(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByHeartRate')
            ->willReturn(50.0);

        $race = new RaceAnalysis($activity, 1.1, 50.0);

        $this->assertEqualsWithDelta(55.0, $race->getVO2maxByHeartRateAfterCorrection(), 0.0000001);
    }

    public function testGetVO2maxByHeartRateAfterCorrectionWithFactorOfOneReturnsUnchangedValue(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByHeartRate')
            ->willReturn(50.0);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame(50.0, $race->getVO2maxByHeartRateAfterCorrection());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDurationsAndExpectedStrings')]
    public function testGetDurationFormatsUsingTotalHoursNeverWrappingToDays(int $seconds, string $expected): void
    {
        $activity = $this->createActivityMock();
        $activity->method('duration')
            ->willReturn($seconds);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame($expected, $race->getDuration());
    }

    /**
     * @return array<string, int[]|string[]>
     */
    public static function provideDurationsAndExpectedStrings(): array
    {
        return [
            '1h 1m 1s' => [3661, '1:01:01'],
            'under a minute' => [59, '0:00:59'],
            'exactly one hour' => [3600, '1:00:00'],
            'zero' => [0, '0:00:00'],
            'long duration never wraps to days format' => [90185, '25:03:05'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOutOfRangeVO2maxShapeValues')]
    public function testGetPrognosisByShapeReturnsFallbackForOutOfRangeVO2max(float $invalidShape): void
    {
        $activity = $this->createActivityMock();
        $activity->method('distance')
            ->willReturn(10.0);

        $race = new RaceAnalysis($activity, 1.0, $invalidShape);

        $this->assertSame('-', $race->getPrognosisByShape());
    }

    /**
     * @return array<string, float[]>
     */
    public static function provideOutOfRangeVO2maxShapeValues(): array
    {
        return [
            'zero (default/unset)' => [0.0],
            'just below minimum (15)' => [14.9],
            'just above maximum (90)' => [90.1],
            'far below minimum' => [-10.0],
        ];
    }

    public function testGetShapeDeviationReturnsFallbackForOutOfRangeVO2maxShape(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('duration')
            ->willReturn(3600);
        $activity->method('distance')
            ->willReturn(10.0);

        $race = new RaceAnalysis($activity, 1.0, 0.0);

        $this->assertSame('-', $race->getShapeDeviation());
    }

    public function testGetPrognosisByHeartRateReturnsFallbackWhenActivitysVO2maxIsOutOfRange(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByHeartRate')
            ->willReturn(5.0);
        $activity->method('distance')
            ->willReturn(10.0);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame('-', $race->getPrognosisByHeartRate());
    }

    public function testGetPrognosisByHeartRateAfterCorrectionReturnsFallbackWhenCorrectedValueIsOutOfRange(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByHeartRate')
            ->willReturn(50.0);
        $activity->method('distance')
            ->willReturn(10.0);

        $race = new RaceAnalysis($activity, 0.1, 50.0);

        $this->assertSame('-', $race->getPrognosisByHeartRateAfterCorrection());
    }

    public function testGetPrognosisByShapeReturnsHoursMinutesSecondsFormatForValidVO2max(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('distance')
            ->willReturn(10.0);

        $race = new RaceAnalysis($activity, 1.0, 50.0);
        $result = $race->getPrognosisByShape();

        $this->assertMatchesRegularExpression('/^\d+:\d{2}:\d{2}$/', $result);
    }

    public function testGetPrognosisByShapeReturnsFallbackWhenDistanceIsNull(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('distance')
            ->willReturn(null);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $this->assertSame('-', $race->getPrognosisByShape());
    }

    public function testGetPrognosisByHeartRateReturnsHoursMinutesSecondsFormatForValidVO2max(): void
    {
        $activity = $this->createActivityMock();
        $activity->method('vo2maxByHeartRate')
            ->willReturn(45.0);
        $activity->method('distance')
            ->willReturn(10.0);

        $race = new RaceAnalysis($activity, 1.0, 50.0);

        $result = $race->getPrognosisByHeartRate();

        $this->assertMatchesRegularExpression('/^\d+:\d{2}:\d{2}$/', $result);
    }
}
