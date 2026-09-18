<?php

/**
 * This file contains class::Table
 * @package Runalyze\View\Window\Laps
 */

namespace Runalyze\View\Window\Laps;

use Ajax;
use FormularFieldset;
use Helper;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Elevation;
use Runalyze\Activity\Pace;
use Runalyze\Data\Laps\Laps;
use Runalyze\Model\Activity;
use Runalyze\Model\Swimdata;
use Runalyze\Parser\Activity\FileType\FitWorkoutStep;
use Runalyze\Profile\Sport\SportProfile;
use Runalyze\View\Activity\Dataview;

/**
 * Display table for laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\View\Window\Laps
 */
class Table
{
    /**
     * @var string
     */
    public const CSS_ID = 'rounds-info-window';

    protected Pace $AveragePace;

    protected ?Duration $AverageTime = null;

    protected int $IndexActive = 1;

    protected int $IndexResting = 1;

    protected array $AdditionalKeys = [];

    /**
     * contains the decoded splits-additional infos as a object array.
     */
    protected array $splitsAdditional = [];

    private bool $showIntensity;

    public function __construct(
        protected Laps $Laps,
        protected Duration $DemandedTime,
        protected Pace $DemandedPace,
        int $internalSportProfileEnum,
        ?array $splitsAdditional = null
    ) {
        $this->defineAdditionalKeys($internalSportProfileEnum === SportProfile::RUNNING);

        // #TSC: parse the additional infos (only if it has the same lap count)
        if (! empty($splitsAdditional) && count($splitsAdditional['data']) === $this->Laps->num()) {
            $this->splitsAdditional = $splitsAdditional;
        }

        // only show column intensity, if more types than ACTIVE and REST available
        $this->showIntensity = $this->Laps->hasMoreThanActiveOrRestLaps();
    }

    protected function defineAdditionalKeys(bool $isRunning)
    {
        $additionalKeys = [];
        foreach ($this->Laps->objects() as $lap) {
            $additionalKeys = array_merge($additionalKeys, $lap->additionalValues());
        }

        $this->AdditionalKeys = array_keys($additionalKeys);

        if (! $isRunning) {
            $this->AdditionalKeys = array_diff($this->AdditionalKeys, [
                Activity\Entity::GROUNDCONTACT,
                Activity\Entity::GROUNDCONTACT_BALANCE,
                Activity\Entity::VERTICAL_OSCILLATION,
                Activity\Entity::VERTICAL_RATIO,
                Activity\Entity::STRIDE_LENGTH,
                Activity\Entity::VO2MAX,
            ]);
        }
    }

    public function setAverage(Pace $pace, ?Duration $time = null): void
    {
        $this->AveragePace = $pace;
        $this->AverageTime = $time;
    }

    /**
     * Display rounds
     */
    public function display(): void
    {
        $Fieldset = new FormularFieldset(__('Laps'));
        $Fieldset->setId('rounds');
        $Fieldset->setHtmlCode($this->tableHeader() . $this->tableBody() . $this->tableFooter());
        $Fieldset->display();

        Ajax::createTablesorterFor('#' . self::CSS_ID, true);
    }

    protected function tableHeader(): string
    {
        // #TSC make width as possible with 100%
        return '<table class="fullwidth zebra-style zebra-blue" id="' . self::CSS_ID . '" style="width: 100%">' .
                '<thead>' .
                    '<tr>' .
                        '<th class="{sorter: \'order\'}">#</th>' .
                        ($this->showIntensity ? '<th class="{sorter: \'type\'}">' . __('Type') . '</th>' : '') .
                        '<th class="{sorter: \'distance\'}">' . __('Distance') . '</th>' .
                        '<th class="{sorter: \'resulttime\'}">' . __('Time') . '</th>' .
                        '<th class="{sorter: \'distance\'}">' . __('Lap') . '</th>' .
                        '<th class="{sorter: \'resulttime\'}">' . __('Duration') . '</th>' .
                        ($this->DemandedTime->isZero() ? '' : '<th>' . __('Diff.') . '</th>') .
                        '<th>' . ($this->DemandedPace->unit()->isTimeFormat() ? __('Pace') : __('Speed')) . '</th>' .
                        ($this->DemandedPace->isEmpty() ? '' : '<th>' . __('Diff.') . '</th>') .
                        '<th>' . __('avg.') . ' ' . __('HR') . '</th>' .
                        '<th>' . __('max.') . ' ' . __('HR') . '</th>' .
                        '<th class="{sorter: false}">' . __('elevation') . '</th>' .
                        $this->tableHeaderForAdditionalKeys() .
                        $this->tableHeaderForSplitsAdditional() .
                    '</tr>' .
                '</thead>';
    }

    protected function tableHeaderForAdditionalKeys(): string
    {
        $Code = '';

        foreach ($this->AdditionalKeys as $key) {
            $Code .= '<th class="small">' . $this->labelForAdditionalValue($key) . '</th>';
        }

        return $Code;
    }

    /**
     * shows the table header for the split-additional informations.
     * this is dynamical regarding the json content.
     * #TSC
     */
    protected function tableHeaderForSplitsAdditional(): string
    {
        $Code = '';

        // parse the json and select all keys
        if ($this->splitsAdditional !== []) {
            foreach ($this->splitsAdditional['keys'] as $key) {
                $Code .= '<th class="small">' . ucfirst($key) . '</th>';
            }
        }

        return $Code;
    }

    protected function tableBody(): string
    {
        $Code = '<tbody class="top-and-bottom-border">';

        for ($i = 0, $num = $this->Laps->num(); $i < $num; ++$i) {
            $Code .= $this->tableRowFor($i);
        }

        return $Code . '</tbody>';
    }

    protected function tableRowFor(int $i): string
    {
        $Lap = $this->Laps->at($i);
        if ($Lap->pace() instanceof \Runalyze\Activity\Pace) {
            $Lap->pace()
                ->setUnit($this->DemandedPace->unit());
        }

        return '<tr class="c ' . ($Lap->isActive() ? '' : 'unimportant') . '">' .
                '<td class="small">' . ($Lap->isActive() ? ($this->IndexActive++) . '.' : '(' . ($this->IndexResting++) . '.)') . '</td>' .
                ($this->showIntensity ? '<td>' . __($Lap->getMode()->getLabel()) . '</td>' : '') .
                '<td>' . ($Lap->hasTrackValues() ? $Lap->trackDistance()->string() : '-') . '</td>' .
                '<td>' . ($Lap->hasTrackValues() ? $Lap->trackDuration()->string() : '-') . '</td>' .
                '<td>' . $Lap->distance()->string() . '</td>' .
                '<td>' . $Lap->duration()->string() . '</td>' .
                ($this->DemandedTime->isZero() ? '' : '<td>' . $Lap->duration()->compareTo($this->DemandedTime, true) . '</td>') .
                '<td>' . ($Lap->pace() instanceof \Runalyze\Activity\Pace ? $Lap->pace()->valueWithAppendix() : '-') . '</td>' .
                ($this->DemandedPace->isEmpty() ? '' : '<td>' . ($Lap->pace() instanceof \Runalyze\Activity\Pace ? $Lap->pace()->compareTo($this->DemandedPace) : '-') . '</td>') .
                '<td>' . ($Lap->hasHR() ? Helper::Unknown($Lap->HRavg()->string(), '-') : '-') . '</td>' .
                '<td>' . ($Lap->hasHR() ? Helper::Unknown($Lap->HRmax()->string(), '-') : '-') . '</td>' .
                '<td>' . ($Lap->hasElevation() ? '+' . Elevation::format($Lap->elevationUp(), false) . '/-' . Elevation::format($Lap->elevationDown(), false) : '-') . '</td>' .
                $this->additionalTableCellsFor($Lap) .
                $this->splitsAdditionalCells($i) .
            '</tr>';
    }

    protected function additionalTableCellsFor(\Runalyze\Data\Laps\Lap $Lap): string
    {
        $Code = '';

        // #TSC show no values for rest-laps
        if (! $Lap->isActive()) {
            if (count($this->AdditionalKeys) > 0) {
                return $Code . '<td colspan="' . count($this->AdditionalKeys) . '"></td>';
            }

            return $Code;
        }

        $View = new Dataview(new Activity\Entity(
            $Lap->additionalValues()
        ));

        foreach ($this->AdditionalKeys as $key) {
            match ($key) {
                Activity\Entity::CADENCE => $Code .= '<td>' . $View->cadence()->asString() . '</td>',
                Activity\Entity::GROUNDCONTACT => $Code .= '<td>' . $View->groundcontact() . '</td>',
                Activity\Entity::GROUNDCONTACT_BALANCE => $Code .= '<td>' . $View->groundcontactBalance() . '</td>',
                Activity\Entity::VERTICAL_OSCILLATION => $Code .= '<td>' . $View->verticalOscillation() . '</td>',
                Activity\Entity::VERTICAL_RATIO => $Code .= '<td>' . $View->verticalRatio() . '</td>',
                Activity\Entity::STRIDE_LENGTH => $Code .= '<td>' . $View->strideLength()->string() . '</td>',
                Activity\Entity::VO2MAX => $Code .= '<td>' . $View->vo2max()->value() . '</td>',
                Activity\Entity::POWER => $Code .= '<td>' . $View->power() . '</td>',
                // #TSC new info from swimming; i'm not using the $View for this
                Swimdata\Entity::STROKE => $Code .= '<td>' . round($Lap->additionalValues()[$key], 1) . '</td>',
                Activity\Entity::TOTAL_STROKES, Swimdata\Entity::SWOLF, Swimdata\Entity::SWOLFCYCLES, 'lanes' => $Code .= '<td>' . round($Lap->additionalValues()[$key]) . '</td>',
                default => $Code .= '<td></td>',
            };
        }

        return $Code;
    }

    protected function labelForAdditionalValue(string $key): string
    {
        return match ($key) {
            Activity\Entity::CADENCE => __('Cadence'),
            Activity\Entity::GROUNDCONTACT => __('Ground contact time'),
            Activity\Entity::GROUNDCONTACT_BALANCE => __('Ground contact balance'),
            Activity\Entity::VERTICAL_OSCILLATION => __('Vertical oscillation'),
            Activity\Entity::VERTICAL_RATIO => __('Vertical ratio'),
            Activity\Entity::STRIDE_LENGTH => __('Stride length'),
            Activity\Entity::VO2MAX => 'VO<sub>2</sub>max',
            Activity\Entity::POWER => __('Power'),
            'lanes' => '# ' . __('Lanes'),
            Swimdata\Entity::STROKE => '&#8709; ' . __('Strokes') . '/' . __('Track'),
            Activity\Entity::TOTAL_STROKES => __('Total strokes'),
            Swimdata\Entity::SWOLF => '&#8709; ' . __('Swolf'),
            Swimdata\Entity::SWOLFCYCLES => '&#8709; ' . __('SWOLFcycles'),
            default => '',
        };
    }

    /**
     * hows the cell values from the json.
     * this is dynamical regarding the json content.
     * #TSC
     */
    protected function splitsAdditionalCells(int $idx): string
    {
        $Code = '';

        if ($this->splitsAdditional === []) {
            return $Code;
        }

        $obj = $this->splitsAdditional['data'][$idx];

        // clone a pace & HR from the first lap as "template"
        $pace = clone $this->Laps->at(0)
            ->pace();
        $pace->setTime(1);
        // set one second
        $hr = clone $this->Laps->at(0)
            ->HRavg();

        foreach ($this->splitsAdditional['keys'] as $key) {
            if (array_key_exists($key, $obj)) {
                if (str_contains($key, FitWorkoutStep::ADD_TARGET_SPEED)) {
                    // if additional is a pace-unit
                    $pace->setDistance($obj[$key] / 1000); // unit is m/s: set km in this one second
                    $Code .= '<td>' . $pace->valueWithAppendix() . '</td>';
                } elseif (str_contains($key, FitWorkoutStep::ADD_TARGET_HR)) {
                    // if additional is a HR
                    $hr->setBPM($obj[$key]);
                    $Code .= '<td>' . $hr->string() . '</td>';
                } else {
                    $Code .= '<td>' . $obj[$key] . '</td>';
                }
            } else {
                $Code .= '<td></td>';
            }
        }

        return $Code;
    }

    protected function tableFooter(): string
    {
        $Code = '<tbody>';
        $Code .= '<tr class="no-zebra"><td colspan="' . ($this->showIntensity ? '5' : '4') . '" class="r">' . __('Average') . ':</td>';
        $Code .= '<td class="c">' . ($this->AverageTime instanceof Duration ? $this->AverageTime->string() : '') . '</td>';
        $Code .= ($this->DemandedTime->isZero() ? '' : '<td></td>');
        $Code .= '<td class="c">' . $this->AveragePace->valueWithAppendix() . '</td>';
        $Code .= ($this->DemandedPace->isEmpty() ? '' : '<td></td>');
        $Code .= '<td colspan="' . (3 + count($this->AdditionalKeys)) . '"></td>';
        $Code .= '</tr>';
        $Code .= '</tbody>';
        $Code .= '</table>';

        return $Code . $this->checkboxToToggleInactiveSplits();
    }

    protected function checkboxToToggleInactiveSplits(): string
    {
        if ($this->IndexActive === 1 || $this->IndexResting === 1) {
            return '';
        }

        $Code = '<p class="checkbox-first">';
        $Code .= '<label>';
        $Code .= '<input type="checkbox" name="toggle-active-splits" id="toggle-active-splits" checked> ';
        $Code .= __('Show inactive splits');
        $Code .= '</label>';
        $Code .= '</p>';

        return $Code . Ajax::wrapJS(
            '$("#toggle-active-splits").click(function(){' .
                '$("#' . self::CSS_ID . ' tr.unimportant").toggle();' .
            '});'
        );
    }
}
