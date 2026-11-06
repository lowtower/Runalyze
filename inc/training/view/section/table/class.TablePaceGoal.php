<?php

/**
 * This file contains class::TablePaceGoal
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Parameter\Application\PaceUnit;
use Runalyze\View\Activity\Context;

/**
 * Display PaceGoal / PacePro.
 * #TSC
 *
 * @author codeproducer
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TablePaceGoal
{
    public Distance $distance;

    // Tolerance in % between stored running values and run-values from the plan; if the diff is higher than the tolerance, a warning is shown
    protected float $TolerancePercent = 3.5;

    protected string $GreenTooltip = '<i class="fa fa-fw fa-info-circle atRight" rel="tooltip" data-original-title="Plus/green means you are faster/better than<br/>your goal. Minus/red you are slower/worse."></i>';

    public function __construct(
        protected Context $context
    ) {
        $this->distance = new Distance(0);
    }

    /**
     * Data for the twig template `activity/training_view/table.html.twig`
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        // activity() = Runalyze\Model\Activity\Entity
        $inPaceGoal = $this->context->activity()->paceGoal();
        $activityTimeS = $this->context->activity()->duration();
        $activityDistanceM = $this->context->activity()->distance() * 1000;

        $paceGoal = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_KM);
        $paceRun = new Pace(0, 100 / 1000, PaceUnit::MIN_PER_KM);
        $durationGoal = new Duration(0);
        $durationRun = new Duration(0);
        $sumPpDistM = 0;
        $sumDistM = 0;
        $sumPpTimeS = 0;
        $sumTimeS = 0;

        $rows = [];
        $count = 1;

        foreach ($inPaceGoal as $split) {
            $sumPpDistM += $split['pp_dist_m'];
            $sumDistM += $split['dist_m'];
            $sumPpTimeS += $split['pp_t_s'];
            $sumTimeS += $split['t_s'];

            $durationGoal->fromSeconds($split['pp_t_s']);
            $durationRun->fromSeconds($split['t_s']);
            $this->setPace($paceGoal, $split['pp_dist_m'], $split['pp_t_s']);
            $this->setPace($paceRun, $split['dist_m'], $split['t_s']);

            $rows[] = [
                'style' => '',
                'cells' => [
                    $this->cell((string) $count++),
                    $this->goalCell($this->formatDistance($split['pp_dist_m'])),
                    $this->cell($this->formatDistance($split['dist_m'])),
                    $this->cell($this->formatDistance($sumDistM)),
                    $this->goalCell($durationGoal->string()),
                    $this->cell($durationRun->string()),
                    $this->cell($durationGoal->compareTo($durationRun)),
                    $this->goalCell($paceGoal->valueWithAppendix()),
                    $this->cell($paceRun->valueWithAppendix()),
                    $this->cell($paceRun->compareTo($paceGoal), 'padding-right: 20px;'),
                ],
            ];
        }

        $durationGoal->fromSeconds($sumPpTimeS);
        $durationRun->fromSeconds($sumTimeS);
        $this->setPace($paceGoal, $sumPpDistM, $sumPpTimeS);
        $this->setPace($paceRun, $sumDistM, $sumTimeS);

        $summaryRow = [
            'style' => 'border-top: 1px solid #666;',
            'cells' => [
                $this->cell(''),
                $this->goalCell($this->formatDistance($sumPpDistM)),
                $this->cell($this->formatDistance($sumDistM)),
                $this->cell($this->formatDistance($sumDistM)),
                $this->goalCell($durationGoal->string()),
                $this->cell($durationRun->string()),
                $this->cell($durationGoal->compareTo($durationRun)),
                $this->goalCell($paceGoal->valueWithAppendix()),
                $this->cell($paceRun->valueWithAppendix()),
                $this->cell($paceRun->compareTo($paceGoal), 'padding-right: 20px;'),
            ],
        ];

        return [
            'class' => 'fullwidth zebra-style',
            // set height, so that the layout not change why change the tab
            'wrapper_style' => 'height: 200px; overflow: scroll;',
            'warning' => $this->warningFor($activityTimeS, $activityDistanceM, $sumTimeS, $sumDistM),
            'header' => $this->headerTemplateData(),
            'bodies' => [
                [
                    'rows' => $rows,
                ],
                [
                    'rows' => [$summaryRow],
                ],
            ],
        ];
    }

    /**
     * Check if the current activity and the IST values from the pace-goal are "valid"
     *
     * @param int $activityTimeS [s]
     * @param float $activityDistanceM [m]
     * @return string empty if the values match the goal
     */
    private function warningFor(int $activityTimeS, float $activityDistanceM, int|float $sumTimeS, int|float $sumDistM): string
    {
        $diffTime = round(abs(100 / $sumTimeS * $activityTimeS - 100), 1);
        $diffDist = round(abs(100 / $sumDistM * $activityDistanceM - 100), 1);

        if ($diffTime >= $this->TolerancePercent || $diffDist >= $this->TolerancePercent) {
            return HTML::warning('Your activity distance (' . $this->formatDistance((int) $activityDistanceM) . ', ' . $diffDist . '%) and/or '
                . 'duration (' . Duration::format($activityTimeS) . ', ' . $diffTime . '%) differ more than ' . $this->TolerancePercent . '% from your goal values. '
                . 'Maybe you have changed your activity data after the import and the Pace-Goal values are out-dated.');
        }

        return '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function headerTemplateData(): array
    {
        return [
            $this->cell('#'),
            $this->goalCell(__('Distance') . ' ' . __('Goal')),
            $this->cell(__('Distance')),
            $this->cell(__('Distance') . ' &sum;'),
            $this->goalCell(__('Time') . ' ' . __('Goal')),
            $this->cell(__('Time')),
            $this->cell(__('Time') . ' &Delta; ' . $this->GreenTooltip),
            $this->goalCell(__('Pace') . ' ' . __('Goal')),
            $this->cell(__('Pace')),
            $this->cell(__('Pace') . ' &Delta; ' . $this->GreenTooltip, 'padding-right: 20px;'),
        ];
    }

    protected function formatDistance(int $meter): string
    {
        $this->distance->set($meter / 1000);
        return $this->distance->stringMeter();
    }

    protected function setPace(Pace $pace, int $meter, int $seconds)
    {
        $pace->setTime($seconds);
        $pace->setDistance($meter / 1000);
    }

    /**
     * @return array<string, mixed>
     */
    private function cell(string $content, string $style = ''): array
    {
        return [
            'content' => $content,
            'style' => $style,
            'colspan' => 0,
            'italic' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function goalCell(string $content, string $style = ''): array
    {
        return [
            'content' => $content,
            'style' => $style,
            'colspan' => 0,
            'italic' => true,
        ];
    }
}
