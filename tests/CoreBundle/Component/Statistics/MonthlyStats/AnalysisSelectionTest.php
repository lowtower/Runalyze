<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Statistics\MonthlyStats;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisSelection;

#[\PHPUnit\Framework\Attributes\Group('default')]
class AnalysisSelectionTest extends TestCase
{
    public function testHasNoCurrentKeyByDefault(): void
    {
        $selection = new AnalysisSelection();

        $this->assertFalse($selection->hasCurrentKey());
    }

    public function testHasCurrentKeyAfterConstructingWithAKnownKey(): void
    {
        $selection = new AnalysisSelection(AnalysisSelection::DISTANCE);

        $this->assertTrue($selection->hasCurrentKey());
        $this->assertSame(AnalysisSelection::DISTANCE, $selection->getCurrentKey());
    }

    public function testHasNoCurrentKeyForAnUnknownKey(): void
    {
        $selection = new AnalysisSelection('unknown-key');

        $this->assertFalse($selection->hasCurrentKey());
    }

    public function testSetCurrentKeyChangesTheCurrentKey(): void
    {
        $selection = new AnalysisSelection();
        $selection->setCurrentKey(AnalysisSelection::ENERGY);

        $this->assertTrue($selection->hasCurrentKey());
        $this->assertSame(AnalysisSelection::ENERGY, $selection->getCurrentKey());
    }

    public function testGetListContainsAllKnownKeys(): void
    {
        $selection = new AnalysisSelection();

        $this->assertSame([
            AnalysisSelection::DISTANCE => 'by distance',
            AnalysisSelection::TIME => 'by time',
            AnalysisSelection::ELEVATION => 'by elevation',
            AnalysisSelection::ENERGY => 'by energy',
            AnalysisSelection::TRIMP => 'by trimp',
            AnalysisSelection::NUMBER => 'by number',
        ], $selection->getList());
    }

    public function testGetCurrentLabelReturnsLabelForCurrentKey(): void
    {
        $selection = new AnalysisSelection(AnalysisSelection::TRIMP);

        $this->assertSame('by trimp', $selection->getCurrentLabel());
    }

    public function testGetCurrentLabelFallsBackToFirstEntryWithoutACurrentKey(): void
    {
        $selection = new AnalysisSelection();

        $this->assertSame('by distance', $selection->getCurrentLabel());
    }
}
