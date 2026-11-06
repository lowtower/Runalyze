<?php

/**
 * This file contains class::TablePauses
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Duration;
use Runalyze\Activity\HeartRate;
use Runalyze\View\Activity\Context;

/**
 * Display pauses
 *
 * @author codeproducer
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TablePauses
{
    public function __construct(
        protected Context $context
    ) {}

    /**
     * Data for the twig template `activity/training_view/table.html.twig`
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $pauses = $this->context->trackdata()->pauses();
        $fullTime = $this->context->Activity()->elapsedTime();

        $hr = new HeartRate(0, \Runalyze\Context::Athlete());
        $allPause = 0;
        $rows = [];

        $len = count($pauses->asArray());

        for ($i = 0; $i < $len; ++$i) {
            $pause = $pauses->at($i);

            $cells = [
                self::cell(($i + 1) . '.'),
                self::cell(Duration::format($pause->time())),
                self::cell(Duration::format($pause->duration())),
            ];

            if ($pause->hasHeartRateInfo()) {
                $hr->setBPM($pause->HRstart());
                $cells[] = self::cell($hr->inBPM() > 0 ? $hr->string() : '-');

                $hr->setBPM(-$pause->hrDiff());
                $cells[] = self::cell($hr->inBPM() !== 0 ? $hr->string() : '-');

                $hr->setBPM($pause->HRend());
                $cells[] = self::cell($hr->inBPM() > 0 ? $hr->string() : '-', 'padding-right: 20px;');
            } else {
                $cells[] = ['content' => '', 'style' => '', 'colspan' => 3, 'italic' => false];
            }

            $allPause += $pause->duration();

            $rows[] = ['style' => '', 'cells' => $cells];
        }

        return [
            'class' => 'fullwidth zebra-style',
            'wrapper_style' => '',
            'warning' => '',
            'header' => $this->headerTemplateData(),
            'bodies' => [
                ['rows' => $rows],
                ['rows' => $this->summaryRowsTemplateData($allPause, $fullTime)],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function headerTemplateData(): array
    {
        return [
            self::cell('#'),
            self::cell(__('Time')),
            self::cell(__('Duration')),
            self::cell(__('Heart rate') . ' (' . __('Start') . ')'),
            self::cell(__('Difference')),
            self::cell(__('Heart rate') . ' (' . __('End') . ')', 'padding-right: 20px;'),
        ];
    }

    /**
     * Summary rows: total pause time and its share of the total time
     *
     * @param float $allPause [s]
     * @param float $fullTime [s]
     * @return array<int, array<string, mixed>>
     */
    private function summaryRowsTemplateData(float $allPause, float $fullTime): array
    {
        $rows = [
            [
                'style' => 'border-top: 1px solid #666;',
                'cells' => [
                    ['content' => '', 'style' => '', 'colspan' => 2, 'italic' => false],
                    ['content' => Duration::format($allPause), 'style' => '', 'colspan' => 0, 'italic' => false],
                    ['content' => '', 'style' => 'padding-right: 20px;', 'colspan' => 3, 'italic' => false],
                ],
            ],
        ];

        // fullTime must be set, but go to secure side ;-)
        if ($fullTime > 0) {
            $rows[] = [
                'style' => '',
                'cells' => [
                    ['content' => '', 'style' => '', 'colspan' => 2, 'italic' => false],
                    ['content' => round(100 / $fullTime * $allPause) . '% of ' . Duration::format($fullTime), 'style' => '', 'colspan' => 0, 'italic' => false],
                    ['content' => '', 'style' => 'padding-right: 20px;', 'colspan' => 3, 'italic' => false],
                ],
            ];
        }

        return $rows;
    }

    /**
     * @param string $content
     * @param string $style
     * @return array<string, mixed>
     */
    private static function cell(string $content, string $style = '')
    {
        return ['content' => $content, 'style' => $style, 'colspan' => 0, 'italic' => false];
    }
}
