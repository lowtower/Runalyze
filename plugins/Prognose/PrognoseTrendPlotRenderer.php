<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use Plot;
use Runalyze\Util\LocalTime;
use Throwable;

final readonly class PrognoseTrendPlotRenderer
{
    /**
     * @param array<string, float> $prognosis
     * @param array<string, float> $prognosisWithEndurance
     * @param list<array{time: int, id: int, official_time: float}> $raceResults
     * @param null|callable(int): bool $isFunCompetition
     * @return array{plot_id: string, html: string, javascript: string}
     *
     * @throws Throwable
     */
    public function render(
        float $distance,
        array $prognosis,
        array $prognosisWithEndurance,
        array $raceResults,
        bool $hasHistory,
        ?callable $isFunCompetition = null
    ): array {
        $results = [];

        if ($hasHistory) {
            foreach ($raceResults as $result) {
                if ($isFunCompetition !== null && $isFunCompetition($result['id'])) {
                    continue;
                }

                $results[(new LocalTime($result['time']))->toServerTimestamp() . '000'] = $result['official_time'] * 1000;
            }
        }

        $plotId = 'formverlauf_' . str_replace('.', '_', (string) $distance);
        $plot = new Plot($plotId, 800, 450);

        $plot->Data[] = [
            'label' => __('Prognosis'),
            'color' => '#880000',
            'data' => $prognosis,
            'lines' => ['show' => true],
            'points' => ['show' => false],
            'curvedLines' => ['apply' => false],
        ];
        $plot->Data[] = [
            'label' => __('Prognosis with marathon shape'),
            'color' => '#000088',
            'data' => $prognosisWithEndurance,
            'lines' => ['show' => true],
            'points' => ['show' => false],
            'curvedLines' => ['apply' => false],
        ];
        $plot->Data[] = [
            'label' => __('Result'),
            'color' => '#000000',
            'data' => $results,
            'lines' => ['show' => false],
            'points' => ['show' => true],
            'curvedLines' => ['apply' => false],
        ];

        $plot->setZeroPointsToNull();
        $plot->setMarginForGrid(5);
        $plot->setXAxisAsTime();
        $plot->addYAxis(1, 'left');
        $plot->setYAxisTimeFormat($prognosis !== [] && max($prognosis) > 1000 * 3600 ? '%H:%M:%S' : '%M:%S');

        if (! $hasHistory) {
            $plot->raiseError(__('No data available.'));
        }

        $html = Plot::getDivFor($plotId, 800, 450);

        ob_start();

        try {
            $plot->outputJavaScript();
            $javascript = ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return [
            'plot_id' => $plotId,
            'html' => $html,
            'javascript' => $javascript === false ? '' : $javascript,
        ];
    }
}
