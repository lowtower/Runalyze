<?php

/**
 * This file contains class::SectionHeartrate
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Section: Heartrate
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionHeartrate extends TrainingViewSectionTabbedPlot
{
    protected function setHeaderAndRows()
    {
        $this->Header = __('Heartrate data');

        $this->appendRowTabbedPlot(new SectionHeartrateRow($this->Context));
    }

    /**
     * Has the training all required data?
     */
    protected function hasRequiredData(): bool
    {
        return $this->Context->activity()
            ->hrAvg() > 0;
    }

    /**
     * CSS-ID
     */
    protected function cssId(): string
    {
        return 'heartrate';
    }
}
