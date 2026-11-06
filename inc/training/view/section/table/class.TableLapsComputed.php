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
    protected function setCode()
    {
        $this->constructLaps();
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
            } catch (\ValueError) {
                $Distances = [$singleDistance];
            }
        }

        $this->Laps = new Laps();
        $this->Laps->calculateFrom($Distances, $this->Context->trackdata(), $this->Context->route());
    }

    /**
     * Data for the twig template `activity/training_view/table.html.twig`
     *
     * @return array<string, mixed>
     */
    public function templateData(): array
    {
        $showCellForHeartrate = $this->Context->trackdata()
            ->has(Trackdata\Entity::HEARTRATE);
        $showCellForElevation = $this->Context->hasRoute() && $this->Context->route()
            ->hasElevations();

        $header = [
            $this->cell(__('Distance')),
            $this->cell(__('Time')),
            $this->cell(__('Pace')),
        ];

        if ($showCellForHeartrate) {
            $header[] = $this->cell(__('avg.') . ' ' . __('bpm'));
        }

        if ($showCellForElevation) {
            $header[] = $this->cell(__('elev.'));
        }

        $rows = [];
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

            $cells = [
                $this->cell($Lap->trackDistance()->string(true, Distance::$DefaultDecimalsSplitTable)),
                $this->cell($Lap->trackDuration()->string()),
                $this->cell($paceValue . '<small>' . $paceAppendix . '</small>'),
            ];

            if ($showCellForHeartrate) {
                $cells[] = $this->cell($Lap->HRavg()->inBPM() . '<small>bpm</small>');
            }

            if ($showCellForElevation) {
                $cells[] = [
                    'class' => 'c',
                    'style' => '',
                    'content' => '+' . Elevation::format($Lap->elevationUp(), false) . '/-' . Elevation::format($Lap->elevationDown(), false),
                ];
            }

            $rows[] = [
                'class' => 'r',
                'style' => '',
                'cells' => $cells,
            ];
        }

        return [
            'class' => 'fullwidth zebra-style',
            'wrapper_style' => '',
            'warning' => '',
            'header' => $header,
            'bodies' => [
                [
                    'rows' => $rows,
                ],
            ],
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
}
