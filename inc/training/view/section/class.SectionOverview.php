<?php

declare(strict_types=1);

/**
 * This file contains class::SectionOverview
 * @package Runalyze\DataObjects\Training\View\Section
 */
/**
 * Section: Overview
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionOverview extends TrainingViewSection
{
    protected function setHeaderAndRows()
    {
        $this->Header = __('Overview');

        $this->appendRow(new SectionOverviewRow($this->Context));
    }

    /**
     * Has the training all required data?
     */
    protected function hasRequiredData(): bool
    {
        return true;
    }
}
