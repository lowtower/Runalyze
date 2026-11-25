<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Statistics\MonthlyStats\Tests;

use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Statistics\MonthlyStats\MonthlyStatisticsMetricSelection;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class MonthlyStatisticsMetricSelectionTest extends TestCase
{
    public function testHasNoCurrentKeyByDefault(): void
    {
        $selection = new MonthlyStatisticsMetricSelection();

        $this->assertFalse($selection->hasCurrentKey());
    }

    public function testHasCurrentKeyAfterConstructingWithAKnownKey(): void
    {
        $selection = new MonthlyStatisticsMetricSelection(MonthlyStatisticsMetricSelection::DISTANCE);

        $this->assertTrue($selection->hasCurrentKey());
        $this->assertSame(MonthlyStatisticsMetricSelection::DISTANCE, $selection->getCurrentKey());
    }

    public function testHasNoCurrentKeyForAnUnknownKey(): void
    {
        $selection = new MonthlyStatisticsMetricSelection('unknown-key');

        $this->assertFalse($selection->hasCurrentKey());
    }

    public function testSetCurrentKeyChangesTheCurrentKey(): void
    {
        $selection = new MonthlyStatisticsMetricSelection();
        $selection->setCurrentKey(MonthlyStatisticsMetricSelection::ENERGY);

        $this->assertTrue($selection->hasCurrentKey());
        $this->assertSame(MonthlyStatisticsMetricSelection::ENERGY, $selection->getCurrentKey());
    }

    public function testGetListContainsAllKnownKeys(): void
    {
        $selection = new MonthlyStatisticsMetricSelection();

        $this->assertSame([
            MonthlyStatisticsMetricSelection::DISTANCE => 'by distance',
            MonthlyStatisticsMetricSelection::TIME => 'by time',
            MonthlyStatisticsMetricSelection::ELEVATION => 'by elevation',
            MonthlyStatisticsMetricSelection::ENERGY => 'by energy',
            MonthlyStatisticsMetricSelection::TRIMP => 'by trimp',
            MonthlyStatisticsMetricSelection::NUMBER => 'by number',
        ], $selection->getList());
    }

    public function testGetCurrentLabelReturnsLabelForCurrentKey(): void
    {
        $selection = new MonthlyStatisticsMetricSelection(MonthlyStatisticsMetricSelection::TRIMP);

        $this->assertSame('by trimp', $selection->getCurrentLabel());
    }

    public function testGetCurrentLabelFallsBackToFirstEntryWithoutACurrentKey(): void
    {
        $selection = new MonthlyStatisticsMetricSelection();

        $this->assertSame('by distance', $selection->getCurrentLabel());
    }
}
