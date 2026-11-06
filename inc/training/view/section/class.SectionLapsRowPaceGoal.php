<?php

/**
 * This file contains class::SectionLapsRowPaceGoal
 * @package Runalyze\DataObjects\Training\View\Section
 */

/**
 * Row: Laps PaceGoal / PacePro #TSC
 *
 * Here some trick: the SectionLapsRowComputed is left the laps-table and right normaly a plot.
 * But for this, the right content should be a table. So use no plot and override the plotTemplateData() method.
 *
 * @author codeproducer
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionLapsRowPaceGoal extends SectionLapsRowComputed
{
    /**
     * Template data for the right content / PaceGoal table.
     *
     * @return array<string, mixed>
     */
    protected function plotTemplateData(): array
    {
        $Table = new TablePaceGoal($this->Context);

        return [
            'kind' => 'raw',
            'html' => $Table->getCode(),
        ];
    }
}
