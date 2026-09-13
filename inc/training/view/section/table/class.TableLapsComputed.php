<?php

/**
 * This file contains class::TableLapsComputed
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Activity\Distance;
use Runalyze\Activity\Elevation;
use Runalyze\Configuration;
use Runalyze\Data\Laps\Laps;
use Runalyze\Model\Trackdata;

/**
 * Table: computed laps
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class TableLapsComputed extends TableLapsAbstract
{
    /**
     * Data
     */
    protected array $Data = [];

    protected function setCode()
    {
        $this->constructLaps();
        $this->setDataToCode();
    }

    protected function constructLaps()
    {
        $singleDistance = Configuration::General()->distanceUnitSystem()->distanceToKmFactor();
        $totalDistance = $this->Context->trackdata()
            ->totalDistance();

        if ($totalDistance < 2 * $singleDistance) {
            $Distances = [$singleDistance];
        } else {
            try {
                $Distances = range($singleDistance, $totalDistance, $singleDistance);
            } catch (\ValueError $valueError) {
                $Distances = [$singleDistance];
            }
        }

        $this->Laps = new Laps();
        $this->Laps->calculateFrom($Distances, $this->Context->trackdata(), $this->Context->route());
    }

    /**
     * Display data
     */
    protected function setDataToCode()
    {
        $showCellForHeartrate = $this->Context->trackdata()
            ->has(Trackdata\Entity::HEARTRATE);
        $showCellForElevation = $this->Context->hasRoute() && $this->Context->route()
            ->hasElevations();

        $this->Code .= '<table class="fullwidth zebra-style">';
        $this->Code .= '<thead><tr>';
        $this->Code .= '<th>' . __('Distance') . '</th>';
        $this->Code .= '<th>' . __('Time') . '</th>';
        $this->Code .= '<th>' . __('Pace') . '</th>';
        if ($showCellForHeartrate) {
            $this->Code .= '<th>' . __('avg.') . ' ' . __('bpm') . '</th>';
        }

        if ($showCellForElevation) {
            $this->Code .= '<th>' . __('elev.') . '</th>';
        }

        $this->Code .= '</tr></thead>';

        $this->Code .= '<tbody>';

        $unit = $this->Context->sport()
            ->legacyPaceUnit();
        foreach ($this->Laps->objects() as $Lap) {
            $paceValue = '';
            $paceAppendix = '';
            if ($Lap->pace()) {
                $Lap->pace()
                    ->setUnit($unit);
                $paceValue = $Lap->pace()
                    ->value();
                $paceAppendix = $Lap->pace()
                    ->appendix();
            }

            $this->Code .= '<tr class="r">';
            $this->Code .= '<td>' . $Lap->trackDistance()->string(true, Distance::$DefaultDecimalsSplitTable) . '</td>';
            $this->Code .= '<td>' . $Lap->trackDuration()->string() . '</td>';
            $this->Code .= '<td>' . $paceValue . '<small>' . $paceAppendix . '</small></td>';
            if ($showCellForHeartrate) {
                $this->Code .= '<td>' . $Lap->HRavg()->inBPM() . '<small>bpm</small></td>';
            }

            if ($showCellForElevation) {
                $this->Code .= '<td class="c">+' . Elevation::format($Lap->elevationUp(), false) . '/-' . Elevation::format($Lap->elevationDown(), false) . '</td>';
            }

            $this->Code .= '</tr>';
        }

        $this->Code .= '</tbody>';
        $this->Code .= '</table>';
    }
}
