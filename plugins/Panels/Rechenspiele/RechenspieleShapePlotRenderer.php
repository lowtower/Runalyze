<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele;

use Plot;
use Throwable;

/**
 * Renders previously calculated shape series with the existing Plot API.
 */
final readonly class RechenspieleShapePlotRenderer
{
    /**
     * @param array<string, float> $atl
     * @param array<string, float> $ctl
     * @param array<string, float> $tsb
     * @param array<string, float> $trimp
     * @param array<string, float> $vo2max
     * @param array<string, float> $dailyVo2max
     * @param array<string, int> $marathonShape
     * @return array{plot_id: string, html: string, javascript: string}
     *
     * @throws Throwable if plot output fails
     */
    public function render(
        string $range,
        string $model,
        array $atl,
        array $ctl,
        array $tsb,
        array $trimp,
        array $vo2max,
        array $dailyVo2max,
        array $marathonShape,
        bool $showInPercent,
        float $maxTrimp,
        bool $hasData,
        ?int $limitedYear = null,
        ?int $futureStartTimestamp = null,
        ?int $futureEndTimestamp = null
    ): array {
        $plotId = 'form' . $range . $model;
        $plot = new Plot($plotId, 800, 450);

        $plot->Data[] = [
            'label' => __('Fitness (CTL)'),
            'color' => '#008800',
            'data' => $ctl,
        ];

        if ($model === 'banister') {
            $plot->Data[] = [
                'label' => 'TSB',
                'color' => '#BBBB00',
                'data' => $tsb,
            ];
        }

        $plot->Data[] = [
            'label' => __('Fatigue (ATL)'),
            'color' => '#CC2222',
            'data' => $atl,
        ];
        $plot->Data[] = [
            'label' => __('avg.') . ' VO2max',
            'color' => '#000000',
            'data' => $vo2max,
            'yaxis' => 2,
        ];
        $plot->Data[] = [
            'label' => 'TRIMP',
            'color' => '#5555FF',
            'data' => $trimp,
            'yaxis' => 3,
        ];
        $plot->Data[] = [
            'label' => 'VO2max',
            'color' => '#444444',
            'data' => $dailyVo2max,
            'yaxis' => 2,
        ];
        $plot->Data[] = [
            'label' => __('Marathon shape'),
            'color' => '#CC9322',
            'data' => $marathonShape,
            'yaxis' => 4,
        ];

        $plot->setMarginForGrid(5);
        $plot->setLinesFilled([0]);

        if ($model === 'banister') {
            $plot->setLinesFilled([2], 0.3);
        }

        $plot->setLinesFilled([1], 0.4);
        $plot->setXAxisAsTime();

        if ($limitedYear !== null) {
            $plot->setXAxisLimitedTo($limitedYear);
        }

        $plot->addYAxis(1, 'left');
        $plot->setYTicks(1, 1);

        if ($showInPercent) {
            $plot->addYUnit(1, '%');
            $plot->setYLimits(1, 0, 100);
        }

        $plot->addYAxis(2, 'right');
        $plot->setYTicks(2, 1, true);
        $plot->addYAxis(3, 'right');
        $plot->setYLimits(3, 0, $maxTrimp * 2);
        $plot->addYAxis(4, 'right');
        $plot->addYUnit(4, '%');

        $maxShape = $marathonShape === [] ? 100 : ceil(max($marathonShape) / 100) * 100;
        $plot->setYLimits(4, 0, $maxShape);

        if ($model === 'banister') {
            $plot->showAsBars(4, 1, 2);
            $plot->showAsPoints(5);
        } else {
            $plot->showAsBars(3, 1, 2);
            $plot->showAsPoints(4);
        }

        $plot->smoothing(false);

        if ($futureStartTimestamp !== null && $futureEndTimestamp !== null && $hasData) {
            $plot->addMarkingArea(
                'x',
                $futureStartTimestamp * 1000,
                $futureEndTimestamp * 1000,
                'rgba(255,255,255,0.3)'
            );
        }

        $plot->setGridAboveData();

        if (! $hasData) {
            $plot->raiseError(__('No data available.'));
        }

        $html = Plot::getDivFor($plotId, 800, 450);

        ob_start();

        try {
            $plot->outputJavaScript();
            $javascript = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        return [
            'plot_id' => $plotId,
            'html' => $html,
            'javascript' => $javascript === false ? '' : $javascript,
        ];
    }
}
