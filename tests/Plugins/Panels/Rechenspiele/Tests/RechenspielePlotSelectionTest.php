<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspielePlotSelection;

#[Group('plugin')]
final class RechenspielePlotSelectionTest extends TestCase
{
    public function testDefaultsToLastHalfYearAndTsb(): void
    {
        $selection = RechenspielePlotSelection::fromQuery([], 2014, 2026);

        $this->assertSame('lasthalf', $selection->range);
        $this->assertSame('tsb', $selection->model);
    }

    public function testAcceptsEverySupportedNamedTimeRange(): void
    {
        foreach (['all', 'lasthalf', 'lastyear'] as $range) {
            $selection = RechenspielePlotSelection::fromQuery([
                'y' => $range,
            ], 2014, 2026);

            $this->assertSame($range, $selection->range);
        }
    }

    public function testAcceptsTheFirstAndLastAvailableYear(): void
    {
        $this->assertSame('2014', RechenspielePlotSelection::fromQuery([
            'y' => '2014',
        ], 2014, 2026)->range);
        $this->assertSame('2026', RechenspielePlotSelection::fromQuery([
            'y' => '2026',
        ], 2014, 2026)->range);
    }

    public function testRejectsYearsOutsideAccountRange(): void
    {
        foreach (['2013', '2027', '0000', '9999', '2026abc', '-1', '2026.0'] as $range) {
            $selection = RechenspielePlotSelection::fromQuery([
                'y' => $range,
            ], 2014, 2026);

            $this->assertSame('lasthalf', $selection->range);
        }
    }

    public function testRejectsUnexpectedParameterTypesWithoutWarnings(): void
    {
        foreach ([[
            'y' => ['all'],
        ], [
            'y' => 2026,
        ], [
            'y' => null,
        ], [
            'y' => new \stdClass(),
        ]] as $query) {
            $selection = RechenspielePlotSelection::fromQuery($query, 2014, 2026);

            $this->assertSame('lasthalf', $selection->range);
        }
    }

    public function testSupportsTsbAndBanisterModels(): void
    {
        $this->assertSame('tsb', RechenspielePlotSelection::fromQuery([
            'm' => 'tsb',
        ], 2014, 2026)->model);
        $this->assertSame('banister', RechenspielePlotSelection::fromQuery([
            'm' => 'banister',
        ], 2014, 2026)->model);
    }

    public function testRejectsUnsupportedModelsAndNonStringValues(): void
    {
        foreach (['invalid', 'Banister', '', ['tsb'], null, 4] as $model) {
            $selection = RechenspielePlotSelection::fromQuery([
                'm' => $model,
            ], 2014, 2026);

            $this->assertSame('tsb', $selection->model);
        }
    }

    public function testEnumeratesOnlySupportedModelsAndYearsInDescendingOrder(): void
    {
        $this->assertSame(['tsb', 'banister'], RechenspielePlotSelection::availableModels());
        $this->assertSame([
            'all',
            'lasthalf',
            'lastyear',
            '2026',
            '2025',
            '2024',
        ], RechenspielePlotSelection::availableRanges(2024, 2026));
    }

    public function testDoesNotIncludeYearsWhenStartYearIsInTheFuture(): void
    {
        $this->assertSame([
            'all',
            'lasthalf',
            'lastyear',
        ], RechenspielePlotSelection::availableRanges(2027, 2026));
    }
}
