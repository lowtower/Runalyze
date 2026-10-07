<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Statistics\MonthlyStats;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisData;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisSelection;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableTime;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableValue;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use RuntimeException;

#[\PHPUnit\Framework\Attributes\Group('default')]
class AnalysisDataTest extends TestCase
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
    protected function getAnalysisDataWithEmptySelections(array $results = []): AnalysisData
    {
        return new AnalysisData(
            new Selection([]),
            new AnalysisSelection(),
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

    public function testEmptyAnalysisData(): void
    {
        $data = $this->getAnalysisDataWithEmptySelections();

        $this->assertTrue($data->isEmpty());
        $this->assertSame(0.0, $data->getRawValue(1970, 1));
        $this->assertNotEquals(0, $data->getMaximum());
    }

    public function testYearRange(): void
    {
        $data = $this->getAnalysisDataWithEmptySelections([
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
        $this->assertSame(42.0, $data->getRawValue(2006, 7));
        $this->assertSame(0.0, $data->getRawValue(2007, 7));
        $this->assertSame(3.14, $data->getRawValue(2009, 1));
        $this->assertSame(42.0, $data->getMaximum());
    }

    /**
     * @throws RuntimeException
     */
    public function testGetYearsThrowsWhenNoDataIsAvailable(): void
    {
        $data = $this->getAnalysisDataWithEmptySelections();

        $this->expectException(RuntimeException::class);
        $data->getYears();
    }

    public function testGetMaximumDefaultsToOneWithoutHigherValues(): void
    {
        $data = $this->getAnalysisDataWithEmptySelections([
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
        $data = $this->getAnalysisDataWithEmptySelections();

        $this->expectException(RuntimeException::class);
        $data->getValue(2023, 5);
    }

    public function testGetValueForTimeSelectionReturnsDisplayableTime(): void
    {
        $data = new AnalysisData(
            new Selection([]),
            new AnalysisSelection(AnalysisSelection::TIME),
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

    public function testGetValueForNumberSelectionReturnsDisplayableValueWithEmptyUnit(): void
    {
        $data = new AnalysisData(
            new Selection([]),
            new AnalysisSelection(AnalysisSelection::NUMBER),
            $this->getTrainingRepositoryMock(),
            new Account()
        );
        $data->setValueExtension($this->createStub(ValueExtension::class));

        $value = $data->getValue(2023, 5);

        $this->assertInstanceOf(DisplayableValue::class, $value);
        $this->assertSame('', $value->getUnit());
    }

    public function testGetValueForDistanceSelectionDelegatesToValueExtension(): void
    {
        $expected = new DisplayableValue(10.5, 'km');
        $valueExtension = $this->createMock(ValueExtension::class);
        $valueExtension->expects($this->once())
            ->method('distance')
            ->with(10.5)
            ->willReturn($expected);

        $data = new AnalysisData(
            new Selection([]),
            new AnalysisSelection(AnalysisSelection::DISTANCE),
            $this->getTrainingRepositoryMock([
                [
                    'year' => 2023,
                    'month' => 5,
                    'value' => 10.5,
                ],
            ]),
            new Account()
        );
        $data->setValueExtension($valueExtension);

        $this->assertSame($expected, $data->getValue(2023, 5));
    }

    public function testGetSportSelectionReturnsTheProvidedSelection(): void
    {
        $sportSelection = $this->createSportSelection('1');

        $data = new AnalysisData(
            $sportSelection,
            new AnalysisSelection(),
            $this->getTrainingRepositoryMock(),
            new Account()
        );

        $this->assertSame($sportSelection, $data->getSportSelection());
    }

    public function testGetAnalysisSelectionReturnsTheProvidedSelection(): void
    {
        $analysisSelection = new AnalysisSelection(AnalysisSelection::ENERGY);

        $data = new AnalysisData(
            new Selection([]),
            $analysisSelection,
            $this->getTrainingRepositoryMock(),
            new Account()
        );

        $this->assertSame($analysisSelection, $data->getAnalysisSelection());
    }

    public function testConstructorPassesFilteredColumnAndSportIdToRepository(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('getMonthlyStatsFor')
            ->with($this->isInstanceOf(Account::class), 'kcal', '1')
            ->willReturn([]);

        new AnalysisData(
            $this->createSportSelection('1'),
            new AnalysisSelection(AnalysisSelection::ENERGY),
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

        new AnalysisData(
            $this->createSportSelection(SportSelectionFactory::ALL),
            new AnalysisSelection(AnalysisSelection::DISTANCE),
            $repository,
            new Account()
        );
    }
}
