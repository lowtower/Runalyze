<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotRenderer;

#[Group('plugin')]
final class RechenspieleShapePlotRendererTest extends TestCase
{
    public function testTsbPlotRetainsTheSevenLegacySeriesAndAxes(): void
    {
        $plot = $this->render('tsb');

        $this->assertSame('formlasthalftsb', $plot['plot_id']);
        $this->assertStringContainsString('formlasthalftsb', $plot['html']);
        $this->assertStringContainsString('formlasthalftsb', $plot['javascript']);
        $this->assertStringContainsString('Fitness (CTL)', $plot['javascript']);
        $this->assertStringContainsString('Fatigue (ATL)', $plot['javascript']);
        $this->assertStringContainsString('TRIMP', $plot['javascript']);
        $this->assertStringContainsString('Marathon shape', $plot['javascript']);
        $this->assertStringContainsString('VO2max', $plot['javascript']);
    }

    public function testBanisterPlotAddsTsbSeries(): void
    {
        $plot = $this->render('banister');

        $this->assertSame('formlasthalfbanister', $plot['plot_id']);
        $this->assertStringContainsString('formlasthalfbanister', $plot['javascript']);
        $this->assertStringContainsString('TSB', $plot['javascript']);
    }

    public function testMissingHistoryProducesPlotErrorWithoutThrowing(): void
    {
        $plot = new RechenspieleShapePlotRenderer()->render(
            'lastyear',
            'tsb',
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            false,
            0.0,
            false
        );

        $this->assertSame('formlastyeartsb', $plot['plot_id']);
        $this->assertNotSame('', $plot['javascript']);
        $this->assertStringContainsString('formlastyeartsb', $plot['html']);
    }

    /**
     * @return array{plot_id: string, html: string, javascript: string}
     */
    private function render(string $model): array
    {
        return new RechenspieleShapePlotRenderer()->render(
            'lasthalf',
            $model,
            [
                '1760000000000' => 24.0,
            ],
            [
                '1760000000000' => 18.0,
            ],
            [
                '1760000000000' => -6.0,
            ],
            [
                '1760000000000' => 50.0,
            ],
            [
                '1760000000000' => 45.0,
            ],
            [
                '1760000000000' => 46.0,
            ],
            [
                '1760000000000' => 70,
            ],
            true,
            50.0,
            true
        );
    }
}
