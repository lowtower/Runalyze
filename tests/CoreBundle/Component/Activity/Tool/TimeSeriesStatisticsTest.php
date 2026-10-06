<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Activity\Tool;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\TimeSeriesStatistics;
use Runalyze\Mathematics\Distribution\EmpiricalDistribution;
use Runalyze\Mathematics\Distribution\MultipleTimeSeries;
use Runalyze\Model\Trackdata\Entity as TrackdataEntity;

class TimeSeriesStatisticsTest extends TestCase
{
    /**
     * @param string[] $availableKeys
     */
    private function createTrackdataMock(array $availableKeys = [TrackdataEntity::TIME]): TrackdataEntity
    {
        $mock = $this->createMock(TrackdataEntity::class);
        $mock->method('has')
            ->willReturnCallback(
                static fn ($key): bool => in_array($key, $availableKeys, true)
            );
        $mock->method('get')
            ->willReturn([1.0, 2.0, 3.0]);

        return $mock;
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
        $this->expectExceptionMessage('Provided trackdata object must have time array.');

        new TimeSeriesStatistics($trackdata);
    }

    public function testConstructorSucceedsWhenTrackdataHasTimeSeries(): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);

        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertInstanceOf(TimeSeriesStatistics::class, $stats);
    }

    public function testHasStatisticsForReturnsFalseForTimeKeyItself(): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertFalse($stats->hasStatisticsFor(TrackdataEntity::TIME));
    }

    public function testHasStatisticsForReturnsFalseForKeyNotPresentInTrackdata(): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertFalse($stats->hasStatisticsFor(TrackdataEntity::PACE));
    }

    #[DataProvider('provideSimpleKeyMethodPairs')]
    public function testHasStatisticsForXxxReturnsTrueWhenKeyIsPresent(string $hasMethod, string $key): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME, $key]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertTrue($stats->{$hasMethod}());
    }

    #[DataProvider('provideSimpleKeyMethodPairs')]
    public function testHasStatisticsForXxxReturnsFalseWhenKeyIsAbsent(string $hasMethod, string $key): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);
        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertFalse($stats->{$hasMethod}());
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideSimpleKeyMethodPairs(): array
    {
        return [
            'Pace' => ['hasStatisticsForPace', TrackdataEntity::PACE],
            'HeartRate' => ['hasStatisticsForHeartRate', TrackdataEntity::HEARTRATE],
            'Cadence' => ['hasStatisticsForCadence', TrackdataEntity::CADENCE],
            'StrideLength' => ['hasStatisticsForStrideLength', TrackdataEntity::STRIDE_LENGTH],
            'Power' => ['hasStatisticsForPower', TrackdataEntity::POWER],
            'Groundcontact' => ['hasStatisticsForGroundcontact', TrackdataEntity::GROUNDCONTACT],
            'GroundcontactBalance' => ['hasStatisticsForGroundcontactBalance', TrackdataEntity::GROUNDCONTACT_BALANCE],
            'VerticalOscillation' => ['hasStatisticsForVerticalOscillation', TrackdataEntity::VERTICAL_OSCILLATION],
            'VerticalRatio' => ['hasStatisticsForVerticalRatio', TrackdataEntity::VERTICAL_RATIO],
            'SaturatedHemoglobin' => ['hasStatisticsForSaturatedHemoglobin', TrackdataEntity::SMO2_0],
            'SaturatedHemoglobin2' => ['hasStatisticsForSaturatedHemoglobin2', TrackdataEntity::SMO2_1],
            'TotalHemoglobin' => ['hasStatisticsForTotalHemoglobin', TrackdataEntity::THB_0],
            'TotalHemoglobin2' => ['hasStatisticsForTotalHemoglobin2', TrackdataEntity::THB_1],
            'ImpactGsLeft' => ['hasStatisticsForImpactGsLeft', TrackdataEntity::IMPACT_GS_LEFT],
            'ImpactGsRight' => ['hasStatisticsForImpactGsRight', TrackdataEntity::IMPACT_GS_RIGHT],
            'BrakingGsLeft' => ['hasStatisticsForBrakingGsLeft', TrackdataEntity::BRAKING_GS_LEFT],
            'BrakingGsRight' => ['hasStatisticsForBrakingGsRight', TrackdataEntity::BRAKING_GS_RIGHT],
            'FootstrikeTypeLeft' => ['hasStatisticsForFootstrikeTypeLeft', TrackdataEntity::FOOTSTRIKE_TYPE_LEFT],
            'FootstrikeTypeRight' => ['hasStatisticsForFootstrikeTypeRight', TrackdataEntity::FOOTSTRIKE_TYPE_RIGHT],
            'PronationExcursionLeft' => ['hasStatisticsForPronationExcursionLeft', TrackdataEntity::PRONATION_EXCURSION_LEFT],
            'PronationExcursionRight' => ['hasStatisticsForPronationExcursionRight', TrackdataEntity::PRONATION_EXCURSION_RIGHT],
        ];
    }

    #[DataProvider('provideGetterKeyPairs')]
    public function testGetStatisticsForXxxRequestsDistributionForMatchingKey(string $getMethod, string $expectedKey): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);
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
     * @return array<string, string[]>
     */
    public static function provideGetterKeyPairs(): array
    {
        return [
            'Pace' => ['getStatisticsForPace', TrackdataEntity::PACE],
            'HeartRate' => ['getStatisticsForHeartRate', TrackdataEntity::HEARTRATE],
            'Cadence' => ['getStatisticsForCadence', TrackdataEntity::CADENCE],
            'StrideLength' => ['getStatisticsForStrideLength', TrackdataEntity::STRIDE_LENGTH],
            'Power' => ['getStatisticsForPower', TrackdataEntity::POWER],
            'Groundcontact' => ['getStatisticsForGroundcontact', TrackdataEntity::GROUNDCONTACT],
            'GroundcontactBalance' => ['getStatisticsForGroundcontactBalance', TrackdataEntity::GROUNDCONTACT_BALANCE],
            'VerticalOscillation' => ['getStatisticsForVerticalOscillation', TrackdataEntity::VERTICAL_OSCILLATION],
            'VerticalRatio' => ['getStatisticsForVerticalRatio', TrackdataEntity::VERTICAL_RATIO],
            'SaturatedHemoglobin' => ['getStatisticsForSaturatedHemoglobin', TrackdataEntity::SMO2_0],
            'SaturatedHemoglobin2' => ['getStatisticsForSaturatedHemoglobin2', TrackdataEntity::SMO2_1],
            'TotalHemoglobin' => ['getStatisticsForTotalHemoglobin', TrackdataEntity::THB_0],
            'TotalHemoglobin2' => ['getStatisticsForTotalHemoglobin2', TrackdataEntity::THB_1],
            'ImpactGsLeft' => ['getStatisticsForImpactGsLeft', TrackdataEntity::IMPACT_GS_LEFT],
            'ImpactGsRight' => ['getStatisticsForImpactGsRight', TrackdataEntity::IMPACT_GS_RIGHT],
            'BrakingGsLeft' => ['getStatisticsForBrakingGsLeft', TrackdataEntity::BRAKING_GS_LEFT],
            'BrakingGsRight' => ['getStatisticsForBrakingGsRight', TrackdataEntity::BRAKING_GS_RIGHT],
            'PronationExcursionLeft' => ['getStatisticsForPronationExcursionLeft', TrackdataEntity::PRONATION_EXCURSION_LEFT],
            'PronationExcursionRight' => ['getStatisticsForPronationExcursionRight', TrackdataEntity::PRONATION_EXCURSION_RIGHT],
        ];
    }

    public function testGetStatisticsForFootstrikeTypeLeftRequestsLeftNotRight(): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);
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
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME]);
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
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME, TrackdataEntity::PACE]);

        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertSame($trackdata, $this->getTrackdataProperty($stats));
    }

    #[DataProvider('provideNonIntegerKeys')]
    public function testTrackdataIsClonedWhenAnyNonIntegerKeyIsPresent(string $nonIntegerKey): void
    {
        $trackdata = $this->createTrackdataMock([TrackdataEntity::TIME, $nonIntegerKey]);

        $stats = new TimeSeriesStatistics($trackdata);

        $this->assertNotSame($trackdata, $this->getTrackdataProperty($stats));
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideNonIntegerKeys(): array
    {
        return [
            'ImpactGsLeft' => [TrackdataEntity::IMPACT_GS_LEFT],
            'ImpactGsRight' => [TrackdataEntity::IMPACT_GS_RIGHT],
            'BrakingGsLeft' => [TrackdataEntity::BRAKING_GS_LEFT],
            'BrakingGsRight' => [TrackdataEntity::BRAKING_GS_RIGHT],
            'PronationExcursionLeft' => [TrackdataEntity::PRONATION_EXCURSION_LEFT],
            'PronationExcursionRight' => [TrackdataEntity::PRONATION_EXCURSION_RIGHT],
        ];
    }
}
