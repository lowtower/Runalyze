<?php

/**
 * This file contains class::Object
 * @package Runalyze\View
 */

namespace Runalyze\View\Splits;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Parameter\Application\PaceUnit;

/**
 * Abstract view object
 *
 * @author Hannes Christiansen
 * @package Runalyze\View
 */
class Table
{
    /**
     * Halfs of a competition to display
     */
    protected array $Halfs = [];

    /**
     * Demanded pace
     */
    protected Pace $demandedPace;

    /**
     * Achieved pace
     */
    protected Pace $achievedPace;

    /**
     * Achieved pace (active rounds)
     */
    protected Pace $achievedPaceActive;

    /**
     * Code
     */
    protected string $Code = '';

    /**
     * @param int $PaceUnit enum
     */
    public function __construct(
        protected \Runalyze\Model\Activity\Splits\Entity $Splits,
        protected int $PaceUnit = PaceUnit::MIN_PER_KM
    ) {
        $this->init();
    }

    public function display(): void
    {
        echo $this->code();
    }

    public function code(): string
    {
        $this->setCode();

        return $this->Code;
    }

    private function init(): void
    {
        $this->achievedPace = new Pace($this->Splits->totalTime(), $this->Splits->totalDistance(), $this->PaceUnit);

        if ($this->Splits->hasActiveAndInactiveLaps()) {
            $timeActive = 0;
            $distanceActive = 0;

            foreach ($this->Splits->asArray() as $Split) {
                if ($Split->isActive()) {
                    $timeActive += $Split->time();
                    $distanceActive += $Split->distance();
                }
            }

            $this->achievedPaceActive = new Pace($timeActive, $distanceActive, $this->PaceUnit);
        } else {
            $this->achievedPaceActive = new Pace(0, 0, $this->PaceUnit);
        }

        $this->demandedPace = new Pace(0, 0, $this->PaceUnit);
    }

    public function setDemandedPace(float $demandedPace): void
    {
        $this->demandedPace = new Pace(round($demandedPace), 1, $this->PaceUnit);
    }

    public function setHalfsOfCompetition(array $Halfs): void
    {
        $this->Halfs = $Halfs;
    }

    /**
     * Data for the twig template `activity/training_view/table.html.twig`
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $splitRows = [];
        $hasDistance = $this->appendSplits($splitRows);

        $summaryRows = [];
        $this->appendGoalRow($summaryRows);

        // #TSC: if we have no distance/pace, not necessary
        if ($hasDistance) {
            $this->appendAverageRows($summaryRows);
            $this->appendHalfsRows($summaryRows);
        }

        return [
            'class' => 'fullwidth zebra-style',
            'wrapper_style' => '',
            'warning' => '',
            'header' => $this->headerTemplateData(),
            'bodies' => [
                [
                    'rows' => $splitRows,
                ],
                [
                    'class' => 'top-spacer',
                    'rows' => $summaryRows,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function headerTemplateData(): array
    {
        return [
            $this->headerCell(''),
            $this->headerCell(__('Distance')),
            $this->headerCell(__('Time')),
            $this->headerCell(__('Pace')),
            $this->headerCell(__('diff')),
        ];
    }

    /**
     * Append all splits as rows, separated by an empty line if only active laps are shown.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return bool whether any split comes with distance/pace
     */
    private function appendSplits(array &$rows): bool
    {
        $i = 0;
        $seperated = false;
        $hasDistance = false;

        $showOnlyActive = $this->Splits->hasActiveAndInactiveLaps() && $this->Splits->hasActiveLaps(2);

        foreach ($this->Splits->asArray() as $Split) {
            $PaceObj = $Split->pace($this->PaceUnit);

            if ($showOnlyActive && ! $Split->isActive()) {
                if (! $seperated && ($i > 0)) {
                    $rows[] = $this->separatorRow('space-line');
                    $rows[] = $this->separatorRow('space-line zebra-corrector');
                    $seperated = true;
                }

                continue;
            }

            $seperated = false;

            // #TSC hide distance&pace if not available
            if ($Split->distance() > 0 || ! $PaceObj->isEmpty()) {
                $hasDistance = true;
            }

            $rows[] = [
                'class' => 'r',
                'style' => '',
                'cells' => [
                    $this->numberCell(++$i),
                    $this->cell($hasDistance ? Distance::format($Split->distance(), true, Distance::$DefaultDecimalsSplitTable) : ''),
                    $this->cell(Duration::format($Split->time())),
                    $this->cell($hasDistance ? $PaceObj->value() . '<small>' . $PaceObj->appendix() . '</small>' : ''),
                    $this->cell($hasDistance ? $this->tdForPaceDifference($PaceObj) : ''),
                ],
            ];
        }

        return $hasDistance;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function appendGoalRow(array &$rows): void
    {
        if ($this->demandedPace->isEmpty()) {
            return;
        }

        $rows[] = [
            'class' => 'small r no-zebra',
            'style' => '',
            'cells' => [
                $this->spanCell(__('Goal') . ': ', 3),
                $this->cell($this->demandedPace->valueWithAppendix()),
                $this->cell(''),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function appendAverageRows(array &$rows): void
    {
        if (! $this->achievedPaceActive->isEmpty()) {
            $rows[] = [
                'class' => 'small r no-zebra',
                'style' => '',
                'cells' => [
                    $this->spanCell(__('Average (active)') . ': ', 3),
                    $this->cell($this->achievedPaceActive->valueWithAppendix()),
                    $this->cell($this->tdForPaceDifference($this->achievedPaceActive, true)),
                ],
            ];
        }

        $rows[] = [
            'class' => 'small r no-zebra',
            'style' => '',
            'cells' => [
                $this->spanCell(__('Average (total)') . ': ', 3),
                $this->cell($this->achievedPace->valueWithAppendix()),
                $this->cell(''),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function appendHalfsRows(array &$rows): void
    {
        if ($this->Halfs === []) {
            return;
        }

        $rows[] = [
            'class' => 'no-zebra',
            'style' => '',
            'cells' => [$this->spanCell('&nbsp;', 5)],
        ];
        $rows[] = [
            'class' => 'b bottom-spacer no-zebra',
            'style' => '',
            'cells' => [$this->spanCell(__('1st/2nd Half'), 5)],
        ];

        $totalTime = 0;
        $totalDist = 0;

        foreach ($this->Halfs as $Half) {
            $totalTime += $Half['s'];
            $totalDist += $Half['km'];
        }

        $TotalPace = new Pace($totalTime, $totalDist, $this->PaceUnit);

        for ($i = 0, $num = count($this->Halfs); $i < $num; $i++) {
            $Pace = new Pace($this->Halfs[$i]['s'], $this->Halfs[$i]['km'], $this->PaceUnit);

            $rows[] = [
                'class' => 'r',
                'style' => '',
                'cells' => [
                    $this->cell(''),
                    $this->cell(Distance::format($this->Halfs[$i]['km'], true, Distance::$DefaultDecimalsSplitTable)),
                    $this->cell(Duration::format($this->Halfs[$i]['s'])),
                    $this->cell($Pace->valueWithAppendix()),
                    $this->cell($Pace->compareTo($TotalPace)),
                ],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function separatorRow(string $class): array
    {
        return [
            'class' => $class,
            'style' => '',
            'cells' => [$this->spanCell('', 5)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function numberCell(int $num): array
    {
        return [
            'class' => 'c',
            'style' => '',
            'content' => $num . '.',
        ];
    }

    /**
     * Cell without a class key, so that the template's default class for
     * header cells (r, i.e. right aligned) is applied.
     *
     * @return array<string, mixed>
     */
    private function headerCell(string $content): array
    {
        return [
            'style' => '',
            'content' => $content,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cell(string $content): array
    {
        return [
            'class' => '',
            'style' => '',
            'content' => $content,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function spanCell(string $content, int $colspan): array
    {
        return [
            'class' => '',
            'style' => '',
            'content' => $content,
            'colspan' => $colspan,
        ];
    }

    /**
     * Display
     */
    public function setCode(): void
    {
        $this->Code = '';

        $this->setTableHeader();
        $hasDistance = $this->setSplits();
        $this->setInstruction();
        // #TSC: if we have no distance/pace, not necessary
        if ($hasDistance) {
            $this->setAverage();
            $this->setHalfsOfCompetitionToCode();
        }

        $this->setTableFooter();
    }

    private function setTableHeader(): void
    {
        $this->Code .= '<table class="fullwidth zebra-style"><tbody>';

        $this->Code .= '<thead>';
        $this->Code .= '<tr class="r">';
        $this->Code .= '<th></th>';
        $this->Code .= '<th>' . __('Distance') . '</th>';
        $this->Code .= '<th>' . __('Time') . '</th>';
        $this->Code .= '<th>' . __('Pace') . '</th>';
        $this->Code .= '<th>' . __('diff') . '</th>';
        $this->Code .= '</tr>';
        $this->Code .= '</thead>';
    }

    private function setSplits()
    {
        $i = 0;
        $seperated = false;
        $hasDistance = false;

        $showOnlyActive = $this->Splits->hasActiveAndInactiveLaps() && $this->Splits->hasActiveLaps(2);

        foreach ($this->Splits->asArray() as $Split) {
            $PaceObj = $Split->pace($this->PaceUnit);

            if ($showOnlyActive && ! $Split->isActive()) {
                if (! $seperated && ($i > 0)) { // && ($i < $num_active)) {
                    $this->Code .= '<tr class="space-line"><td colspan="5"></td></tr>';
                    $this->Code .= '<tr class="space-line zebra-corrector"><td colspan="5"></td></tr>';
                    $seperated = true;
                }

                continue;
            }

            $seperated = false;

            // #TSC hide distance&pace if not available
            if ($Split->distance() > 0 || ! $PaceObj->isEmpty()) {
                $hasDistance = true;
            }

            $this->Code .= '<tr class="r">';
            $this->Code .= '<td class="c">' . (++$i) . '.</td>';
            $this->Code .= '<td>' . ($hasDistance ? Distance::format($Split->distance(), true, Distance::$DefaultDecimalsSplitTable) : '') . '</td>';
            $this->Code .= '<td>' . Duration::format($Split->time()) . '</td>';
            $this->Code .= '<td>' . ($hasDistance ? $PaceObj->value() . '<small>' . $PaceObj->appendix() : '') . '</small></td>';
            $this->Code .= '<td>' . ($hasDistance ? $this->tdForPaceDifference($PaceObj) : '') . '</td>';
            $this->Code .= '</tr>';
        }

        $this->Code .= '</tbody><tbody class="top-spacer">';

        return $hasDistance;
    }

    /**
     * Get td for pace difference
     * @param bool $compareOnlyToDemanded [optional] default false
     */
    private function tdForPaceDifference(Pace $Pace, bool $compareOnlyToDemanded = false): string
    {
        if (
            ($compareOnlyToDemanded && ($this->demandedPace->isEmpty()))
            || ($this->achievedPaceActive->isEmpty()) && ($this->achievedPace->isEmpty())
        ) {
            return '';
        }

        if (! $this->demandedPace->isEmpty()) {
            return $Pace->compareTo($this->demandedPace);
        }

        if (! $this->achievedPaceActive->isEmpty()) {
            return $Pace->compareTo($this->achievedPaceActive);
        }

        return $Pace->compareTo($this->achievedPace);
    }

    private function setInstruction(): void
    {
        if ($this->demandedPace->isEmpty()) {
            return;
        }

        $this->Code .= '
			<tr class="small r no-zebra">
				<td colspan="3">' . __('Goal') . ': </td>
					<td>' . $this->demandedPace->valueWithAppendix() . '</td>
				<td></td>
			</tr>';
    }

    private function setAverage(): void
    {
        if (! $this->achievedPaceActive->isEmpty()) {
            $this->Code .= '
				<tr class="small r no-zebra">
					<td colspan="3">' . __('Average (active)') . ': </td>
					<td>' . $this->achievedPaceActive->valueWithAppendix() . '</td>
					<td>' . $this->tdForPaceDifference($this->achievedPaceActive, true) . '</td>
				</tr>';
        }

        $this->Code .= '
			<tr class="small r no-zebra">
				<td colspan="3">' . __('Average (total)') . ': </td>
					<td>' . $this->achievedPace->valueWithAppendix() . '</td>
				<td></td>
			</tr>';
    }

    /**
     * Set halfs of competition
     */
    private function setHalfsOfCompetitionToCode(): void
    {
        if ($this->Halfs === []) {
            return;
        }

        $this->Code .= '<tr class="no-zebra"><td colspan="5">&nbsp;</td></tr>';
        $this->Code .= '<tr class="b bottom-spacer no-zebra"><td colspan="5">' . __('1st/2nd Half') . '</td></tr>';

        $totalTime = 0;
        $totalDist = 0;

        foreach ($this->Halfs as $Half) {
            $totalTime += $Half['s'];
            $totalDist += $Half['km'];
        }

        $TotalPace = new Pace($totalTime, $totalDist, $this->PaceUnit);

        for ($i = 0, $num = count($this->Halfs); $i < $num; $i++) {
            $Pace = new Pace($this->Halfs[$i]['s'], $this->Halfs[$i]['km'], $this->PaceUnit);

            $this->Code .= '<tr class="r">
								<td></td>
								<td>' . Distance::format($this->Halfs[$i]['km'], true, Distance::$DefaultDecimalsSplitTable) . '</td>
								<td>' . Duration::format($this->Halfs[$i]['s']) . '</td>
								<td>' . $Pace->valueWithAppendix() . '</td>
								<td>' . $Pace->compareTo($TotalPace) . '</td>
							</tr>';
        }
    }

    private function setTableFooter(): void
    {
        $this->Code .= '</tbody>';
        $this->Code .= '</table>';
    }
}
