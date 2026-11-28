<?php

/**
 * This file contains class::Object
 * @package Runalyze\View
 */

namespace Runalyze\View\Splits;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Bundle\CoreBundle\Component\Activity\PaceFormatter;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Velocity\Unit\SecondsPerKilometer;

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

    private readonly PaceFormatter $paceFormatter;

    protected int $demandedPace = 0;

    protected int $achievedPace;

    private bool $hasAchievedPace;

    protected ?int $achievedPaceActive = null;

    /**
     * Code
     */
    protected string $Code = '';

    public function __construct(
        protected \Runalyze\Model\Activity\Splits\Entity $Splits,
        AbstractPaceUnit $paceUnit = new SecondsPerKilometer()
    ) {
        $this->paceFormatter = new PaceFormatter($paceUnit);
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
        $totalTime = $this->Splits->totalTime();
        $totalDistance = $this->Splits->totalDistance();
        $this->achievedPace = PaceFormatter::secondsPerKilometer($totalTime, $totalDistance);
        $this->hasAchievedPace = $totalTime > 0 && $totalDistance > 0.0;

        if ($this->Splits->hasActiveAndInactiveLaps()) {
            $timeActive = 0;
            $distanceActive = 0;

            foreach ($this->Splits->asArray() as $Split) {
                if ($Split->isActive()) {
                    $timeActive += $Split->time();
                    $distanceActive += $Split->distance();
                }
            }

            if ($timeActive > 0 && $distanceActive > 0.0) {
                $this->achievedPaceActive = PaceFormatter::secondsPerKilometer($timeActive, $distanceActive);
            }
        }
    }

    public function setDemandedPace(float $demandedPace): void
    {
        $this->demandedPace = (int) round($demandedPace);
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
            if ($showOnlyActive && ! $Split->isActive()) {
                if (! $seperated && ($i > 0)) {
                    $rows[] = $this->separatorRow('space-line');
                    $rows[] = $this->separatorRow('space-line zebra-corrector');
                    $seperated = true;
                }

                continue;
            }

            $seperated = false;

            $pace = PaceFormatter::secondsPerKilometer($Split->time(), $Split->distance());

            // #TSC hide distance&pace if not available
            if ($Split->distance() > 0) {
                $hasDistance = true;
            }

            $rows[] = [
                'class' => 'r',
                'style' => '',
                'cells' => [
                    $this->numberCell(++$i),
                    $this->cell($hasDistance ? Distance::format($Split->distance(), true, Distance::$DefaultDecimalsSplitTable) : ''),
                    $this->cell(Duration::format($Split->time())),
                    $this->cell($hasDistance ? $this->paceFormatter->value($pace) . '<small>' . $this->paceFormatter->appendix() . '</small>' : ''),
                    $this->cell($hasDistance ? $this->tdForPaceDifference($pace) : ''),
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
        if ($this->demandedPace <= 0) {
            return;
        }

        $rows[] = [
            'class' => 'small r no-zebra',
            'style' => '',
            'cells' => [
                $this->spanCell(__('Goal') . ': ', 3),
                $this->cell($this->paceFormatter->format($this->demandedPace)),
                $this->cell(''),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function appendAverageRows(array &$rows): void
    {
        if ($this->achievedPaceActive !== null) {
            $rows[] = [
                'class' => 'small r no-zebra',
                'style' => '',
                'cells' => [
                    $this->spanCell(__('Average (active)') . ': ', 3),
                    $this->cell($this->paceFormatter->format($this->achievedPaceActive)),
                    $this->cell($this->tdForPaceDifference($this->achievedPaceActive, true)),
                ],
            ];
        }

        $rows[] = [
            'class' => 'small r no-zebra',
            'style' => '',
            'cells' => [
                $this->spanCell(__('Average (total)') . ': ', 3),
                $this->cell($this->paceFormatter->format($this->achievedPace)),
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

        $totalPace = PaceFormatter::secondsPerKilometer($totalTime, $totalDist);

        for ($i = 0, $num = count($this->Halfs); $i < $num; $i++) {
            $pace = PaceFormatter::secondsPerKilometer($this->Halfs[$i]['s'], $this->Halfs[$i]['km'] ?? 0.0);

            $rows[] = [
                'class' => 'r',
                'style' => '',
                'cells' => [
                    $this->cell(''),
                    $this->cell(Distance::format($this->Halfs[$i]['km'], true, Distance::$DefaultDecimalsSplitTable)),
                    $this->cell(Duration::format($this->Halfs[$i]['s'])),
                    $this->cell($this->paceFormatter->format($pace)),
                    $this->cell($this->paceFormatter->compare($pace, $totalPace)),
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
            if ($showOnlyActive && ! $Split->isActive()) {
                if (! $seperated && ($i > 0)) { // && ($i < $num_active)) {
                    $this->Code .= '<tr class="space-line"><td colspan="5"></td></tr>';
                    $this->Code .= '<tr class="space-line zebra-corrector"><td colspan="5"></td></tr>';
                    $seperated = true;
                }

                continue;
            }

            $seperated = false;

            $pace = PaceFormatter::secondsPerKilometer($Split->time(), $Split->distance());

            // #TSC hide distance&pace if not available
            if ($Split->distance() > 0) {
                $hasDistance = true;
            }

            $this->Code .= '<tr class="r">';
            $this->Code .= '<td class="c">' . (++$i) . '.</td>';
            $this->Code .= '<td>' . ($hasDistance ? Distance::format($Split->distance(), true, Distance::$DefaultDecimalsSplitTable) : '') . '</td>';
            $this->Code .= '<td>' . Duration::format($Split->time()) . '</td>';
            $this->Code .= '<td>' . ($hasDistance ? $this->paceFormatter->value($pace) . '<small>' . $this->paceFormatter->appendix() : '') . '</small></td>';
            $this->Code .= '<td>' . ($hasDistance ? $this->tdForPaceDifference($pace) : '') . '</td>';
            $this->Code .= '</tr>';
        }

        $this->Code .= '</tbody><tbody class="top-spacer">';

        return $hasDistance;
    }

    /**
     * Get td for pace difference
     * @param bool $compareOnlyToDemanded [optional] default false
     */
    private function tdForPaceDifference(int $pace, bool $compareOnlyToDemanded = false): string
    {
        if (
            ($compareOnlyToDemanded && $this->demandedPace <= 0)
            || ($this->achievedPaceActive === null && ! $this->hasAchievedPace)
        ) {
            return '';
        }

        if ($this->demandedPace > 0) {
            return $this->paceFormatter->compare($pace, $this->demandedPace);
        }

        if ($this->achievedPaceActive !== null) {
            return $this->paceFormatter->compare($pace, $this->achievedPaceActive);
        }

        return $this->paceFormatter->compare($pace, $this->achievedPace);
    }

    private function setInstruction(): void
    {
        if ($this->demandedPace <= 0) {
            return;
        }

        $this->Code .= '
			<tr class="small r no-zebra">
				<td colspan="3">' . __('Goal') . ': </td>
					<td>' . $this->paceFormatter->format($this->demandedPace) . '</td>
				<td></td>
			</tr>';
    }

    private function setAverage(): void
    {
        if ($this->achievedPaceActive !== null) {
            $this->Code .= '
				<tr class="small r no-zebra">
					<td colspan="3">' . __('Average (active)') . ': </td>
					<td>' . $this->paceFormatter->format($this->achievedPaceActive) . '</td>
					<td>' . $this->tdForPaceDifference($this->achievedPaceActive, true) . '</td>
				</tr>';
        }

        $this->Code .= '
			<tr class="small r no-zebra">
				<td colspan="3">' . __('Average (total)') . ': </td>
					<td>' . $this->paceFormatter->format($this->achievedPace) . '</td>
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

        $totalPace = PaceFormatter::secondsPerKilometer($totalTime, $totalDist);

        for ($i = 0, $num = count($this->Halfs); $i < $num; $i++) {
            $pace = PaceFormatter::secondsPerKilometer($this->Halfs[$i]['s'], $this->Halfs[$i]['km'] ?? 0.0);

            $this->Code .= '<tr class="r">
								<td></td>
								<td>' . Distance::format($this->Halfs[$i]['km'], true, Distance::$DefaultDecimalsSplitTable) . '</td>
								<td>' . Duration::format($this->Halfs[$i]['s']) . '</td>
								<td>' . $this->paceFormatter->format($pace) . '</td>
								<td>' . $this->paceFormatter->compare($pace, $totalPace) . '</td>
							</tr>';
        }
    }

    private function setTableFooter(): void
    {
        $this->Code .= '</tbody>';
        $this->Code .= '</table>';
    }
}
