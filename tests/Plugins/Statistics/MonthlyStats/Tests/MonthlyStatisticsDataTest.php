<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\MonthlyStats\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableTime;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableValue;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use Runalyze\Plugins\Statistics\MonthlyStats\MonthlyStatisticsData;
use Runalyze\Plugins\Statistics\MonthlyStats\MonthlyStatisticsMetricSelection;
use RuntimeException;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class MonthlyStatisticsDataTest extends TestCase
{
    /**
     * @param array<int, array<string, int|float>> $results
     */
    protected function getTrainingRepositoryMock(array $results = []): TrainingRepository&Stub
    {
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('getMonthlyStatsFor')
            ->willReturn($results);

        return $repository;
    }

    /**
     * @param array<int, array<string, int|float>> $results
     */
    protected function dataWithEmptySelections(array $results = []): MonthlyStatisticsData
    {
        return new MonthlyStatisticsData(
            new Selection([]),
            new MonthlyStatisticsMetricSelection(),
            $this->getTrainingRepositoryMock($results),
            new Account()
        );
    }

    private function createSportSelection(?string $currentKey = null): Selection
    {
        return new Selection([
            SportSelectionFactory::ALL => 'all sports',
            '1' => 'Running',
        ], $currentKey);
    }

    public function testEmptyMonthlyStatistics(): void
    {
        $data = $this->dataWithEmptySelections();

        $this->assertTrue($data->isEmpty());
        $this->assertEqualsWithDelta(0.0, $data->getRawValue(1970, 1), PHP_FLOAT_EPSILON);
        $this->assertNotEquals(0, $data->getMaximum());
    }

    public function testYearRange(): void
    {
        $data = $this->dataWithEmptySelections([
            [
                'year' => 2006,
                'month' => 7,
                'value' => 42.0,
            ],
            [
                'year' => 2009,
                'month' => 1,
                'value' => 3.14,
            ],
        ]);

        $this->assertFalse($data->isEmpty());
        $this->assertEquals([2009, 2008, 2007, 2006], $data->getYears());
        $this->assertEqualsWithDelta(42.0, $data->getRawValue(2006, 7), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(0.0, $data->getRawValue(2007, 7), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3.14, $data->getRawValue(2009, 1), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(42.0, $data->getMaximum(), PHP_FLOAT_EPSILON);
    }

    /**
     * @throws RuntimeException
     */
    public function testGetYearsThrowsWhenNoDataIsAvailable(): void
    {
        $data = $this->dataWithEmptySelections();

        $this->expectException(RuntimeException::class);
        $data->getYears();
    }

    public function testGetMaximumDefaultsToOneWithoutHigherValues(): void
    {
        $data = $this->dataWithEmptySelections([
            [
                'year' => 2023,
                'month' => 5,
                'value' => 0.5,
            ],
        ]);

        $this->assertEquals(1, $data->getMaximum());
    }

    /**
     * @throws RuntimeException
     */
    public function testGetValueThrowsWithoutValueExtensionHavingBeenSet(): void
    {
        $data = $this->dataWithEmptySelections();

        $this->expectException(RuntimeException::class);
        $data->getValue(2023, 5);
    }

    public function testGetValueForTimeSelectionReturnsDisplayableTime(): void
    {
        $data = new MonthlyStatisticsData(
            new Selection([]),
            new MonthlyStatisticsMetricSelection(MonthlyStatisticsMetricSelection::TIME),
            $this->getTrainingRepositoryMock([
                [
                    'year' => 2023,
                    'month' => 5,
                    'value' => 3600,
                ],
            ]),
            new Account()
        );
        $data->setValueExtension($this->createStub(ValueExtension::class));

        $this->assertInstanceOf(DisplayableTime::class, $data->getValue(2023, 5));
    }

    #[DataProvider('metricsWithoutUnits')]
    public function testGetValueForMetricWithoutUnit(string $metric, ?string $column): void
    {
        $account = new Account();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('getMonthlyStatsFor')
            ->with($account, $column, null)
            ->willReturn([
                [
                    'year' => 2023,
                    'month' => 5,
                    'value' => 10,
                ],
            ]);

        $data = new MonthlyStatisticsData(
            new Selection([]),
            new MonthlyStatisticsMetricSelection($metric),
            $repository,
            $account
        );
        $data->setValueExtension($this->createStub(ValueExtension::class));

        $value = $data->getValue(2023, 5);
        $this->assertInstanceOf(DisplayableValue::class, $value);
        $this->assertEqualsWithDelta(10.0, $value->getRawValue(), PHP_FLOAT_EPSILON);
        $this->assertSame('', $value->getUnit());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function metricsWithoutUnits(): iterable
    {
        yield 'activity count' => [MonthlyStatisticsMetricSelection::NUMBER, null];
        yield 'TRIMP' => [MonthlyStatisticsMetricSelection::TRIMP, 'trimp'];
    }

    #[DataProvider('metricsWithUnits')]
    public function testGetValueUsesTheSelectedMetricFormatter(string $metric, string $column, string $formatter, string $unit): void
    {
        $account = new Account();
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('getMonthlyStatsFor')
            ->with($account, $column, null)
            ->willReturn([
                [
                    'year' => 2023,
                    'month' => 5,
                    'value' => 10.5,
                ],
            ]);

        $expected = new DisplayableValue(10.5, $unit);
        $valueExtension = $this->createMock(ValueExtension::class);
        $valueExtension->expects($this->once())
            ->method($formatter)
            ->with(10.5)
            ->willReturn($expected);

        $data = new MonthlyStatisticsData(
            new Selection([]),
            new MonthlyStatisticsMetricSelection($metric),
            $repository,
            $account
        );
        $data->setValueExtension($valueExtension);

        $this->assertSame($expected, $data->getValue(2023, 5));
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function metricsWithUnits(): iterable
    {
        yield 'distance' => [MonthlyStatisticsMetricSelection::DISTANCE, 'distance', 'distance', 'km'];
        yield 'elevation' => [MonthlyStatisticsMetricSelection::ELEVATION, 'elevation', 'elevation', 'm'];
        yield 'energy' => [MonthlyStatisticsMetricSelection::ENERGY, 'kcal', 'energy', 'kcal'];
    }

    public function testGetSportSelectionReturnsTheProvidedSelection(): void
    {
        $sportSelection = $this->createSportSelection('1');

        $data = new MonthlyStatisticsData(
            $sportSelection,
            new MonthlyStatisticsMetricSelection(),
            $this->getTrainingRepositoryMock(),
            new Account()
        );

        $this->assertSame($sportSelection, $data->getSportSelection());
    }

    public function testGetMetricSelectionReturnsTheProvidedSelection(): void
    {
        $metricSelection = new MonthlyStatisticsMetricSelection(MonthlyStatisticsMetricSelection::ENERGY);

        $data = new MonthlyStatisticsData(
            new Selection([]),
            $metricSelection,
            $this->getTrainingRepositoryMock(),
            new Account()
        );

        $this->assertSame($metricSelection, $data->getMetricSelection());
    }

    public function testConstructorPassesFilteredColumnAndSportIdToRepository(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('getMonthlyStatsFor')
            ->with($this->isInstanceOf(Account::class), 'kcal', 1)
            ->willReturn([]);

        new MonthlyStatisticsData(
            $this->createSportSelection('1'),
            new MonthlyStatisticsMetricSelection(MonthlyStatisticsMetricSelection::ENERGY),
            $repository,
            new Account()
        );
    }

    public function testConstructorPassesNullSportIdWhenAllSportsAreSelected(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('getMonthlyStatsFor')
            ->with($this->isInstanceOf(Account::class), $this->anything(), null)
            ->willReturn([]);

        new MonthlyStatisticsData(
            $this->createSportSelection(SportSelectionFactory::ALL),
            new MonthlyStatisticsMetricSelection(MonthlyStatisticsMetricSelection::DISTANCE),
            $repository,
            new Account()
        );
    }
}
