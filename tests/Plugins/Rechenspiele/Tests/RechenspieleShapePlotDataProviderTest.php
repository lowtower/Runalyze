<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Plugins\Rechenspiele\RechenspieleMarathonShapeSeriesCalculator;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDailyAggregator;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeDataProvider;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotDataProvider;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotDateRange;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotSeriesAssembler;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotTimestampMapper;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeSeriesCalculator;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class RechenspieleShapePlotDataProviderTest extends TestCase
{
    public function testCombinesDailyTrainingWithPerformanceAndMarathonShapeForTheAccount(): void
    {
        $account = new Account();
        $account->setTimezone(Timezone::BERLIN);
        $repository = $this->createMock(TrainingRepository::class);
        $timezone = new DateTimeZone('Europe/Berlin');

        $repository->expects($this->once())
            ->method('findShapePlotTrainingData')
            ->with(
                $account,
                7,
                $this->isType('int'),
                $this->isType('int'),
                false,
                $timezone
            )
            ->willReturn([[
                'day' => '2026-11-23',
                'trimp' => 80.0,
                'distance' => 12.0,
                'vo2max_weighted' => 180000.0,
                'vo2max_sum_time' => 3600.0,
                'is_running' => true,
            ]]);

        $provider = $this->provider($repository, [
            'data.START_TIME' => (string) new DateTimeImmutable('2026-11-01 00:00:00', $timezone)->getTimestamp(),
            'general.RUNNINGSPORT' => '7',
            'trimp.ATL_DAYS' => '7',
            'trimp.CTL_DAYS' => '42',
            'vo2max.VO2MAX_DAYS' => '7',
            'data.MAX_ATL' => '100',
            'data.MAX_CTL' => '100',
        ]);
        $result = $provider->getPlotData($account, 'all', 'tsb');

        self::assertNotNull($result);
        $timestamp = (string) (new DateTimeImmutable('2026-11-23 12:00:00', $timezone)->getTimestamp() * 1000);
        $tomorrow = (string) (new DateTimeImmutable('2026-11-24 12:00:00', $timezone)->getTimestamp() * 1000);

        $this->assertSame(80.0, $result['trimp'][$timestamp]);
        $this->assertEqualsWithDelta(50.0, $result['vo2max'][$tomorrow], 0.00001);
        $this->assertEqualsWithDelta(50.0, $result['vo2max_day'][$timestamp], 0.00001);
        $this->assertArrayHasKey($tomorrow, $result['marathon_shape']);
        $this->assertTrue($result['calculate_maxima']);
        $this->assertTrue($result['show_in_percent']);
        $this->assertNull($result['limited_year']);
        $this->assertTrue($result['mark_future']);
        $this->assertSame($tomorrow, $result['last_timestamp']);
        $this->assertGreaterThan(0, $result['max_atl']);
        $this->assertGreaterThan(0, $result['max_ctl']);
    }

    public function testHistoricalYearUsesStoredMaximaAndBanisterAbsoluteMode(): void
    {
        $account = new Account();
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findShapePlotTrainingData')->willReturn([]);

        $provider = $this->provider($repository, [
            'data.START_TIME' => (string) new DateTimeImmutable('2024-01-01 UTC')->getTimestamp(),
            'data.MAX_ATL' => '123',
            'data.MAX_CTL' => '234',
        ]);

        $result = $provider->getPlotData($account, '2025', 'banister');

        $this->assertNotNull($result);
        $this->assertSame(100, $result['max_atl']);
        $this->assertSame(100, $result['max_ctl']);
        $this->assertFalse($result['calculate_maxima']);
        $this->assertFalse($result['show_in_percent']);
        $this->assertSame(2025, $result['limited_year']);
        $this->assertFalse($result['mark_future']);
        $this->assertSame([], $result['vo2max']);
        $this->assertSame([], $result['marathon_shape']);
    }

    public function testNoTrainingHistorySkipsRepositoryQuery(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findShapePlotTrainingData');

        $this->assertNull($this->provider($repository)->getPlotData(new Account(), 'all', 'tsb'));
    }

    #[DataProvider('invalidModels')]
    public function testRejectsUnsupportedModelBeforeQueryingHistory(string $model): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findShapePlotTrainingData');

        $this->expectException(InvalidArgumentException::class);

        $this->provider($repository)->getPlotData(new Account(), 'all', $model);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidModels(): iterable
    {
        yield 'unsupported model' => ['unknown'];
        yield 'incorrect case' => ['TSB'];
    }

    public function testRejectsInvalidTrainingWindowBeforeRepositoryQuery(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findShapePlotTrainingData');

        $this->expectException(InvalidArgumentException::class);

        $this->provider($repository, [
            'data.START_TIME' => (string) new DateTimeImmutable('2026-11-01 UTC')->getTimestamp(),
            'trimp.ATL_DAYS' => '0',
        ])->getPlotData(new Account(), 'all', 'tsb');
    }

    /**
     * @param array<string, string> $options
     */
    private function provider(?TrainingRepository $repository = null, array $options = []): RechenspieleShapePlotDataProvider
    {
        $repository ??= $this->createStub(TrainingRepository::class);
        $configuration = $this->createStub(ConfigurationManager::class);
        $configuration->method('getList')->willReturn(new RunalyzeConfigurationList($options));

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-11-24 12:00:00 UTC'));
        $mapper = new RechenspieleShapePlotTimestampMapper();

        return new RechenspieleShapePlotDataProvider(
            $configuration,
            $clock,
            new RechenspieleShapePlotDateRange(),
            new RechenspieleShapeDataProvider($repository, new RechenspieleShapeDailyAggregator()),
            new RechenspieleShapeSeriesCalculator(),
            new RechenspieleMarathonShapeSeriesCalculator(),
            new RechenspieleShapePlotSeriesAssembler($mapper),
            $mapper
        );
    }
}
