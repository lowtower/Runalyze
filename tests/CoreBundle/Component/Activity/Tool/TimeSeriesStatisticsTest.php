<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity\Tool;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\TimeSeriesStatistics;
use Runalyze\Mathematics\Distribution\EmpiricalDistribution;
use Runalyze\Mathematics\Distribution\MultipleTimeSeries;
use Runalyze\Model\Trackdata\Entity as TrackdataEntity;

final class TimeSeriesStatisticsTest extends TestCase
{
    /**
     * @param string[] $availableKeys
     */
    private function createTrackdataStub(array $availableKeys = [TrackdataEntity::TIME]): TrackdataEntity
    {
        $stub = $this->createStub(TrackdataEntity::class);
        $stub->method('has')
            ->willReturnCallback(
                static fn (string $key): bool => in_array($key, $availableKeys, true)
            );
        $stub->method('get')
            ->willReturn([1.0, 2.0, 3.0]);

        return $stub;
    }

    private function injectMultipleTimeSeries(TimeSeriesStatistics $stats, MultipleTimeSeries $multipleTimeSeries): void
    {
        $reflection = new \ReflectionObject($stats);
        $property = $reflection->getProperty('MultipleTimeSeries');
        $property->setValue($stats, $multipleTimeSeries);
    }

    public function testConstructorThrowsWhenTrackdataHasNoTimeSeries(): void
    {
        $trackdata = $this->createMock(TrackdataEntity::class);
        $trackdata->expects($this->once())
            ->method('has')
            ->with(TrackdataEntity::TIME)
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Provided trackdata object must have time array.');

        new TimeSeriesStatistics($trackdata);
    }

    public function testConstructorSucceedsWhenTrackdataHasTimeSeries(): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);

        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertInstanceOf(TimeSeriesStatistics::class, $stats);
    }

    public function testHasStatisticsForReturnsFalseForTimeKeyItself(): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertFalse($stats->hasStatisticsFor(TrackdataEntity::TIME));
    }

    public function testHasStatisticsForReturnsFalseForKeyNotPresentInTrackdata(): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertFalse($stats->hasStatisticsFor(TrackdataEntity::PACE));
    }

    #[DataProvider('provideSimpleKeyMethodPairs')]
    public function testHasStatisticsForXxxReturnsTrueWhenKeyIsPresent(string $hasMethod, string $key): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME, $key]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertTrue($stats->{$hasMethod}());
    }

    #[DataProvider('provideSimpleKeyMethodPairs')]
    public function testHasStatisticsForXxxReturnsFalseWhenKeyIsAbsent(string $hasMethod, string $key): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertFalse($stats->{$hasMethod}());
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function provideSimpleKeyMethodPairs(): \Iterator
    {
        yield 'Pace' => ['hasStatisticsForPace', TrackdataEntity::PACE];
        yield 'HeartRate' => ['hasStatisticsForHeartRate', TrackdataEntity::HEARTRATE];
        yield 'Cadence' => ['hasStatisticsForCadence', TrackdataEntity::CADENCE];
        yield 'StrideLength' => ['hasStatisticsForStrideLength', TrackdataEntity::STRIDE_LENGTH];
        yield 'Power' => ['hasStatisticsForPower', TrackdataEntity::POWER];
        yield 'Groundcontact' => ['hasStatisticsForGroundcontact', TrackdataEntity::GROUNDCONTACT];
        yield 'GroundcontactBalance' => ['hasStatisticsForGroundcontactBalance', TrackdataEntity::GROUNDCONTACT_BALANCE];
        yield 'VerticalOscillation' => ['hasStatisticsForVerticalOscillation', TrackdataEntity::VERTICAL_OSCILLATION];
        yield 'VerticalRatio' => ['hasStatisticsForVerticalRatio', TrackdataEntity::VERTICAL_RATIO];
        yield 'SaturatedHemoglobin' => ['hasStatisticsForSaturatedHemoglobin', TrackdataEntity::SMO2_0];
        yield 'SaturatedHemoglobin2' => ['hasStatisticsForSaturatedHemoglobin2', TrackdataEntity::SMO2_1];
        yield 'TotalHemoglobin' => ['hasStatisticsForTotalHemoglobin', TrackdataEntity::THB_0];
        yield 'TotalHemoglobin2' => ['hasStatisticsForTotalHemoglobin2', TrackdataEntity::THB_1];
        yield 'ImpactGsLeft' => ['hasStatisticsForImpactGsLeft', TrackdataEntity::IMPACT_GS_LEFT];
        yield 'ImpactGsRight' => ['hasStatisticsForImpactGsRight', TrackdataEntity::IMPACT_GS_RIGHT];
        yield 'BrakingGsLeft' => ['hasStatisticsForBrakingGsLeft', TrackdataEntity::BRAKING_GS_LEFT];
        yield 'BrakingGsRight' => ['hasStatisticsForBrakingGsRight', TrackdataEntity::BRAKING_GS_RIGHT];
        yield 'FootstrikeTypeLeft' => ['hasStatisticsForFootstrikeTypeLeft', TrackdataEntity::FOOTSTRIKE_TYPE_LEFT];
        yield 'FootstrikeTypeRight' => ['hasStatisticsForFootstrikeTypeRight', TrackdataEntity::FOOTSTRIKE_TYPE_RIGHT];
        yield 'PronationExcursionLeft' => ['hasStatisticsForPronationExcursionLeft', TrackdataEntity::PRONATION_EXCURSION_LEFT];
        yield 'PronationExcursionRight' => ['hasStatisticsForPronationExcursionRight', TrackdataEntity::PRONATION_EXCURSION_RIGHT];
    }

    #[DataProvider('provideGetterKeyPairs')]
    public function testGetStatisticsForXxxRequestsDistributionForMatchingKey(string $getMethod, string $expectedKey): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $distribution = $this->createStub(EmpiricalDistribution::class);
        $multipleTimeSeries = $this->createMock(MultipleTimeSeries::class);
        $multipleTimeSeries->expects($this->once())
            ->method('getDistribution')
            ->with($expectedKey)
            ->willReturn($distribution);

        $this->injectMultipleTimeSeries($stats, $multipleTimeSeries);

        $result = $stats->{$getMethod}();

        $this->assertSame($distribution, $result);
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function provideGetterKeyPairs(): \Iterator
    {
        yield 'Pace' => ['getStatisticsForPace', TrackdataEntity::PACE];
        yield 'HeartRate' => ['getStatisticsForHeartRate', TrackdataEntity::HEARTRATE];
        yield 'Cadence' => ['getStatisticsForCadence', TrackdataEntity::CADENCE];
        yield 'StrideLength' => ['getStatisticsForStrideLength', TrackdataEntity::STRIDE_LENGTH];
        yield 'Power' => ['getStatisticsForPower', TrackdataEntity::POWER];
        yield 'Groundcontact' => ['getStatisticsForGroundcontact', TrackdataEntity::GROUNDCONTACT];
        yield 'GroundcontactBalance' => ['getStatisticsForGroundcontactBalance', TrackdataEntity::GROUNDCONTACT_BALANCE];
        yield 'VerticalOscillation' => ['getStatisticsForVerticalOscillation', TrackdataEntity::VERTICAL_OSCILLATION];
        yield 'VerticalRatio' => ['getStatisticsForVerticalRatio', TrackdataEntity::VERTICAL_RATIO];
        yield 'SaturatedHemoglobin' => ['getStatisticsForSaturatedHemoglobin', TrackdataEntity::SMO2_0];
        yield 'SaturatedHemoglobin2' => ['getStatisticsForSaturatedHemoglobin2', TrackdataEntity::SMO2_1];
        yield 'TotalHemoglobin' => ['getStatisticsForTotalHemoglobin', TrackdataEntity::THB_0];
        yield 'TotalHemoglobin2' => ['getStatisticsForTotalHemoglobin2', TrackdataEntity::THB_1];
        yield 'ImpactGsLeft' => ['getStatisticsForImpactGsLeft', TrackdataEntity::IMPACT_GS_LEFT];
        yield 'ImpactGsRight' => ['getStatisticsForImpactGsRight', TrackdataEntity::IMPACT_GS_RIGHT];
        yield 'BrakingGsLeft' => ['getStatisticsForBrakingGsLeft', TrackdataEntity::BRAKING_GS_LEFT];
        yield 'BrakingGsRight' => ['getStatisticsForBrakingGsRight', TrackdataEntity::BRAKING_GS_RIGHT];
        yield 'PronationExcursionLeft' => ['getStatisticsForPronationExcursionLeft', TrackdataEntity::PRONATION_EXCURSION_LEFT];
        yield 'PronationExcursionRight' => ['getStatisticsForPronationExcursionRight', TrackdataEntity::PRONATION_EXCURSION_RIGHT];
    }

    public function testGetStatisticsForFootstrikeTypeLeftRequestsLeftNotRight(): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $distribution = $this->createStub(EmpiricalDistribution::class);
        $multipleTimeSeries = $this->createMock(MultipleTimeSeries::class);
        $multipleTimeSeries->expects($this->once())
            ->method('getDistribution')
            ->with(TrackdataEntity::FOOTSTRIKE_TYPE_LEFT)
            ->willReturn($distribution);

        $this->injectMultipleTimeSeries($stats, $multipleTimeSeries);

        $result = $stats->getStatisticsForFootstrikeTypeLeft();

        $this->assertSame($distribution, $result);
    }

    public function testGetStatisticsForFootstrikeTypeRightRequestsRightNotLeft(): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $distribution = $this->createStub(EmpiricalDistribution::class);
        $multipleTimeSeries = $this->createMock(MultipleTimeSeries::class);
        $multipleTimeSeries->expects($this->once())
            ->method('getDistribution')
            ->with(TrackdataEntity::FOOTSTRIKE_TYPE_RIGHT)
            ->willReturn($distribution);

        $this->injectMultipleTimeSeries($stats, $multipleTimeSeries);

        $result = $stats->getStatisticsForFootstrikeTypeRight();

        $this->assertSame($distribution, $result);
    }

    private function getTrackdataProperty(TimeSeriesStatistics $stats): TrackdataEntity
    {
        $reflection = new \ReflectionObject($stats);
        $property = $reflection->getProperty('Trackdata');

        return $property->getValue($stats);
    }

    public function testTrackdataIsNotClonedWhenNoNonIntegerKeysArePresent(): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME, TrackdataEntity::PACE]);

        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertSame($trackdata, $this->getTrackdataProperty($stats));
    }

    #[DataProvider('provideNonIntegerKeys')]
    public function testTrackdataIsClonedWhenAnyNonIntegerKeyIsPresent(string $nonIntegerKey): void
    {
        $trackdata = $this->createTrackdataStub([TrackdataEntity::TIME, $nonIntegerKey]);

        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertNotSame($trackdata, $this->getTrackdataProperty($stats));
    }

    /**
     * @return \Iterator<string, array<string>>
     */
    public static function provideNonIntegerKeys(): \Iterator
    {
        yield 'ImpactGsLeft' => [TrackdataEntity::IMPACT_GS_LEFT];
        yield 'ImpactGsRight' => [TrackdataEntity::IMPACT_GS_RIGHT];
        yield 'BrakingGsLeft' => [TrackdataEntity::BRAKING_GS_LEFT];
        yield 'BrakingGsRight' => [TrackdataEntity::BRAKING_GS_RIGHT];
        yield 'PronationExcursionLeft' => [TrackdataEntity::PRONATION_EXCURSION_LEFT];
        yield 'PronationExcursionRight' => [TrackdataEntity::PRONATION_EXCURSION_RIGHT];
    }
}
