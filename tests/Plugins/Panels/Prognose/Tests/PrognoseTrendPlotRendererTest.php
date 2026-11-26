<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendPlotRenderer;

#[Group('plugin')]
final class PrognoseTrendPlotRendererTest extends TestCase
{
    public function testRendersEachSeriesForCustomDistance(): void
    {
        $plot = new PrognoseTrendPlotRenderer()->render(
            5.72,
            [
                '1760000000000' => 1200000.0,
            ],
            [
                '1760000000000' => 1300000.0,
            ],
            [],
            true
        );

        $this->assertSame('formverlauf_5_72', $plot['plot_id']);
        $this->assertStringContainsString('formverlauf_5_72', $plot['html']);
        $this->assertNotSame('', $plot['javascript']);
        $this->assertStringContainsString('formverlauf_5_72', $plot['javascript']);
    }

    public function testFiltersFunCompetitionsWithoutDroppingOfficialResults(): void
    {
        $checked = [];
        $plot = new PrognoseTrendPlotRenderer()->render(
            10.0,
            [],
            [],
            [
                [
                    'id' => 11,
                    'time' => 1750000000,
                    'official_time' => 2200.0,
                ],
                [
                    'id' => 12,
                    'time' => 1750001000,
                    'official_time' => 2300.0,
                ],
            ],
            true,
            static function (int $id) use (&$checked): bool {
                $checked[] = $id;

                return $id === 12;
            }
        );

        $this->assertSame([11, 12], $checked);
        $this->assertStringContainsString('2200000', $plot['javascript']);
        $this->assertStringNotContainsString('2300000', $plot['javascript']);
    }

    public function testMissingHistoryDoesNotInspectRaceResults(): void
    {
        $checked = [];
        $plot = new PrognoseTrendPlotRenderer()->render(
            10.0,
            [],
            [],
            [[
                'id' => 11,
                'time' => 1750000000,
                'official_time' => 2200.0,
            ]],
            false,
            static function (int $id) use (&$checked): bool {
                $checked[] = $id;

                return false;
            }
        );

        $this->assertSame([], $checked);
        $this->assertStringContainsString('formverlauf_10', $plot['html']);
        $this->assertNotSame('', $plot['javascript']);
    }
}
