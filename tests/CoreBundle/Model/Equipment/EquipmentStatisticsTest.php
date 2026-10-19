<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Equipment;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Equipment;
use Runalyze\Bundle\CoreBundle\Model\Equipment\EquipmentStatistic;
use Runalyze\Bundle\CoreBundle\Model\Equipment\EquipmentStatistics;

final class EquipmentStatisticsTest extends TestCase
{
    private \SplObjectStorage $labels;

    protected function setUp(): void
    {
        $this->labels = new \SplObjectStorage();
    }

    /**
     * @return array<int|string, (Stub&Equipment)|int|float|null>
     */
    private function createEquipmentRow(string $label, bool $isActive, ?int $num = 1, ?float $maxPace = 300.0, ?float $maxDistance = 10.0): array
    {
        $equipment = $this->createStub(Equipment::class);
        $equipment->method('isActive')
            ->willReturn($isActive);
        $this->labels[$equipment] = $label;

        return [
            0 => $equipment,
            'num' => $num,
            'max_pace' => $maxPace,
            'max_distance' => $maxDistance,
        ];
    }

    private function labelFor(Equipment $equipment): string
    {
        return $this->labels[$equipment];
    }

    public function testEmptyQueryResultProducesEmptyStatistics(): void
    {
        $statistics = new EquipmentStatistics([]);

        $this->assertSame([], $statistics->getStatistics());
        $this->assertSame(0, $statistics->getCount());
    }

    public function testSingleActiveEquipmentIsIncluded(): void
    {
        $row = $this->createEquipmentRow('shoe-active', true, 5, 280.0, 42.5);

        $statistics = new EquipmentStatistics([$row]);
        $result = $statistics->getStatistics();

        $this->assertCount(1, $result);
        $this->assertSame($row[0], $result[0]->getEquipment());
        $this->assertSame(5, $result[0]->getNumberOfActivities());
        $this->assertEqualsWithDelta(280.0, $result[0]->getMaximalPace(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(42.5, $result[0]->getMaximalDistance(), PHP_FLOAT_EPSILON);
    }

    public function testSingleInactiveEquipmentIsIncluded(): void
    {
        $row = $this->createEquipmentRow('shoe-inactive', false);

        $statistics = new EquipmentStatistics([$row]);
        $result = $statistics->getStatistics();

        $this->assertCount(1, $result);
        $this->assertSame($row[0], $result[0]->getEquipment());
    }

    public function testGetCountMatchesNumberOfRows(): void
    {
        $rows = [
            $this->createEquipmentRow('a', true),
            $this->createEquipmentRow('b', false),
            $this->createEquipmentRow('c', true),
        ];

        $statistics = new EquipmentStatistics($rows);

        $this->assertSame(3, $statistics->getCount());
    }

    public function testActiveEquipmentIsOrderedBeforeInactiveEquipment(): void
    {
        $rows = [
            $this->createEquipmentRow('inactive-1', false),
            $this->createEquipmentRow('active-1', true),
            $this->createEquipmentRow('inactive-2', false),
            $this->createEquipmentRow('active-2', true),
        ];

        $statistics = new EquipmentStatistics($rows);
        $result = $statistics->getStatistics();

        $labels = array_map(fn (EquipmentStatistic $stat): string => $this->labelFor($stat->getEquipment()), $result);

        $this->assertSame(
            ['active-1', 'active-2', 'inactive-1', 'inactive-2'],
            $labels
        );
    }

    public function testAllActiveEquipmentPreservesOriginalOrder(): void
    {
        $rows = [
            $this->createEquipmentRow('first', true),
            $this->createEquipmentRow('second', true),
            $this->createEquipmentRow('third', true),
        ];

        $statistics = new EquipmentStatistics($rows);
        $result = $statistics->getStatistics();

        $labels = array_map(fn (EquipmentStatistic $stat): string => $this->labelFor($stat->getEquipment()), $result);

        $this->assertSame(['first', 'second', 'third'], $labels);
    }

    public function testAllInactiveEquipmentPreservesOriginalOrder(): void
    {
        $rows = [
            $this->createEquipmentRow('first', false),
            $this->createEquipmentRow('second', false),
        ];

        $statistics = new EquipmentStatistics($rows);
        $result = $statistics->getStatistics();

        $labels = array_map(fn (EquipmentStatistic $stat): string => $this->labelFor($stat->getEquipment()), $result);

        $this->assertSame(['first', 'second'], $labels);
    }

    public function testNullableValuesArePropagatedCorrectly(): void
    {
        $row = $this->createEquipmentRow('no-data', true, null, null, null);

        $statistics = new EquipmentStatistics([$row]);
        $result = $statistics->getStatistics();

        $this->assertNull($result[0]->getNumberOfActivities());
        $this->assertNull($result[0]->getMaximalPace());
        $this->assertNull($result[0]->getMaximalDistance());
    }
}
