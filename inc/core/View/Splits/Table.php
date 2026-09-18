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
            ($compareOnlyToDemanded && ($this->demandedPace->isEmpty())) ||
            ($this->achievedPaceActive->isEmpty()) && ($this->achievedPace->isEmpty())
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
