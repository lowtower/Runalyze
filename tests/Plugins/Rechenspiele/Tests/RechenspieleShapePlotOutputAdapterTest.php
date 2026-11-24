<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotOutputAdapter;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotRenderer;

#[Group('plugin')]
final class RechenspieleShapePlotOutputAdapterTest extends TestCase
{
    public function testTsbPlotContainsCalculatedSeriesAndRetainsItsId(): void
    {
        $plot = $this->adapter()->render('lasthalf', 'tsb', $this->plotData());

        $this->assertSame('formlasthalftsb', $plot['plot_id']);
        $this->assertStringContainsString('formlasthalftsb', $plot['html']);
        $this->assertStringContainsString('Fitness (CTL)', $plot['javascript']);
        $this->assertStringContainsString('Marathon shape', $plot['javascript']);
        $this->assertStringContainsString('1760000000000', $plot['javascript']);
    }

    public function testBanisterPlotIncludesTsbAndPreservesYearLimit(): void
    {
        $plotData = $this->plotData();
        $plotData['limited_year'] = 2025;
        $plotData['mark_future'] = false;

        $plot = $this->adapter()->render('2025', 'banister', $plotData);

        $this->assertSame('form2025banister', $plot['plot_id']);
        $this->assertStringContainsString('TSB', $plot['javascript']);
        $this->assertStringContainsString('form2025banister', $plot['html']);
    }

    public function testMissingHistoryUsesTheRendererNoDataState(): void
    {
        $plot = $this->adapter()->render('lastyear', 'tsb', null);

        $this->assertSame('formlastyeartsb', $plot['plot_id']);
        $this->assertStringContainsString('formlastyeartsb', $plot['html']);
        $this->assertNotSame('', $plot['javascript']);
    }

    /**
     * @return array{
     *     atl: array<string, float>,
     *     ctl: array<string, float>,
     *     tsb: array<string, float>,
     *     trimp: array<string, float>,
     *     vo2max: array<string, float>,
     *     vo2max_day: array<string, float>,
     *     marathon_shape: array<string, int>,
     *     max_atl: int,
     *     max_ctl: int,
     *     max_trimp: float,
     *     show_in_percent: bool,
     *     calculate_maxima: bool,
     *     limited_year: ?int,
     *     mark_future: bool,
     *     today_timestamp: int,
     *     last_timestamp: string
     * }
     */
    private function plotData(): array
    {
        return [
            'atl' => ['1760000000000' => 24.0],
            'ctl' => ['1760000000000' => 18.0],
            'tsb' => ['1760000000000' => -6.0],
            'trimp' => ['1760000000000' => 50.0],
            'vo2max' => ['1760000000000' => 45.0],
            'vo2max_day' => ['1760000000000' => 46.0],
            'marathon_shape' => ['1760000000000' => 70],
            'max_atl' => 100,
            'max_ctl' => 100,
            'max_trimp' => 50.0,
            'show_in_percent' => true,
            'calculate_maxima' => false,
            'limited_year' => null,
            'mark_future' => true,
            'today_timestamp' => 1760000000,
            'last_timestamp' => '1760086400000',
        ];
    }

    private function adapter(): RechenspieleShapePlotOutputAdapter
    {
        return new RechenspieleShapePlotOutputAdapter(new RechenspieleShapePlotRenderer());
    }
}
