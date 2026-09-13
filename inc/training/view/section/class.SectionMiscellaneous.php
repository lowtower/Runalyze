<?php

/**
 * This file contains class::SectionMiscellaneous
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\View\Activity\Context;

/**
 * Section: Miscellaneous
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionMiscellaneous extends TrainingViewSectionTabbedPlot
{
    /**
     * @param bool $showCadence
     */
    public function __construct(
        ?Context $Context = null,
        protected $showCadence = true
    ) {
        parent::__construct($Context);
    }

    protected function setHeaderAndRows()
    {
        $this->Header = __('Miscellaneous');

        $this->appendRowTabbedPlot(new SectionMiscellaneousRow($this->Context, $this->showCadence));
    }

    /**
     * Has the training all required data?
     */
    protected function hasRequiredData(): bool
    {
        return true;
    }

    /**
     * CSS-ID
     */
    protected function cssId(): string
    {
        return 'misc';
    }
}
