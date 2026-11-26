<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use Throwable;

/**
 * Adapts account-scoped shape plot data to the legacy-compatible Plot renderer.
 */
final readonly class RechenspieleShapePlotOutputAdapter
{
    public function __construct(
        private RechenspieleShapePlotRenderer $renderer
    ) {}

    /**
     * @param array{
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
     * }|null $plotData
     * @return array{plot_id: string, html: string, javascript: string}
     *
     * @throws Throwable if the plot renderer fails
     */
    public function render(string $range, string $model, ?array $plotData): array
    {
        if ($plotData === null) {
            return $this->renderer->render(
                $range,
                $model,
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
        }

        return $this->renderer->render(
            $range,
            $model,
            $plotData['atl'],
            $plotData['ctl'],
            $plotData['tsb'],
            $plotData['trimp'],
            $plotData['vo2max'],
            $plotData['vo2max_day'],
            $plotData['marathon_shape'],
            $plotData['show_in_percent'],
            $plotData['max_trimp'],
            true,
            $plotData['limited_year'],
            $plotData['mark_future'] ? $plotData['today_timestamp'] : null,
            $plotData['mark_future'] ? intdiv((int) $plotData['last_timestamp'], 1000) : null
        );
    }
}
